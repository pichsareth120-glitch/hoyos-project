<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActivateCustomerRequest;
use App\Http\Requests\Admin\SuspendCustomerRequest;
use App\Models\User;
use App\Services\AdminAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display a paginated listing of customer accounts.
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'customer')
            ->withCount('orders')
            ->withSum(['orders as lifetime_spend' => function ($q) {
                $q->where('payment_status', 'paid')
                  ->whereNotIn('order_status', ['cancelled', 'refunded']);
            }], 'total_amount');

        // Search by name, email, or phone
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by account_status
        if ($status = $request->input('status')) {
            $query->where('account_status', $status);
        }

        // Sorting
        if ($request->input('sort') === 'lifetime_spend') {
            $query->orderByRaw('COALESCE(lifetime_spend, 0) DESC');
        } elseif ($request->input('sort') === 'orders_count') {
            $query->orderByDesc('orders_count');
        } else {
            $query->orderByDesc('id');
        }

        $customers = $query->paginate(20)->withQueryString();

        // Single grouped query for real customer account counts
        $customerStats = User::where('role', 'customer')
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN account_status = 'active' THEN 1 END) as active_count,
                COUNT(CASE WHEN account_status = 'suspended' THEN 1 END) as suspended_count,
                COUNT(CASE WHEN created_at >= ? THEN 1 END) as new_this_month
            ", [now()->startOfMonth()])
            ->first();

        return view('admin.customers.index', [
            'customers' => $customers,
            'customerStats' => $customerStats,
            'filters' => $request->only(['search', 'status', 'sort']),
        ]);
    }

    /**
     * Display customer profile, addresses, order history, and spend metrics.
     */
    public function show(User $user)
    {
        // Admins are never listed or viewable in this module
        if ($user->role !== 'customer' || $user->isAdmin()) {
            abort(404, 'Customer not found.');
        }

        $addresses = $user->addresses()->latest('is_default')->get();
        $orders = $user->orders()->withCount('items')->latest('id')->paginate(10);

        $orderCount = $user->orders()->count();

        $paidOrdersQuery = $user->orders()
            ->where('payment_status', 'paid')
            ->whereNotIn('order_status', ['cancelled', 'refunded']);

        $paidOrdersCount = (clone $paidOrdersQuery)->count();
        $lifetimeSpend = (float) ((clone $paidOrdersQuery)->sum('total_amount') ?? 0);
        $averageOrderValue = $paidOrdersCount > 0 ? ($lifetimeSpend / $paidOrdersCount) : 0;

        return view('admin.customers.show', [
            'customer' => $user,
            'addresses' => $addresses,
            'orders' => $orders,
            'orderCount' => $orderCount,
            'lifetimeSpend' => $lifetimeSpend,
            'averageOrderValue' => $averageOrderValue,
        ]);
    }

    /**
     * Suspend a customer account.
     */
    public function suspend(SuspendCustomerRequest $request, User $user)
    {
        return DB::transaction(function () use ($request, $user) {
            $customer = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            if ($customer->id === Auth::id()) {
                return back()->with('error', 'You cannot suspend your own account.');
            }

            if ($customer->role === 'admin' || $customer->isAdmin()) {
                abort(403, 'Admins cannot be suspended.');
            }

            if ($customer->account_status === 'suspended') {
                return back()->with('error', 'This customer account is already suspended.');
            }

            $oldStatus = $customer->account_status;
            $customer->forceFill([
                'account_status' => 'suspended',
                'remember_token' => \Illuminate\Support\Str::random(60),
            ])->save();

            // When the session driver is "database", purge user's active session rows
            if (config('session.driver') === 'database') {
                $sessionTable = config('session.table', 'sessions');
                if (\Illuminate\Support\Facades\Schema::hasTable($sessionTable)) {
                    DB::table($sessionTable)
                        ->where('user_id', $customer->id)
                        ->delete();
                }
            }

            AdminAuditLogger::log(
                module: 'customers',
                action: 'suspend',
                targetTable: 'users',
                targetId: $customer->id,
                oldValues: ['account_status' => $oldStatus],
                newValues: [
                    'account_status' => 'suspended',
                    'reason' => $request->input('reason'),
                ]
            );

            return back()->with('success', "Customer account {$customer->name} has been suspended.");
        });
    }

    /**
     * Activate a suspended customer account.
     */
    public function activate(ActivateCustomerRequest $request, User $user)
    {
        return DB::transaction(function () use ($user) {
            $customer = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            if ($customer->role === 'admin' || $customer->isAdmin()) {
                abort(403, 'Admins cannot be modified.');
            }

            if ($customer->account_status === 'active') {
                return back()->with('error', 'This customer account is already active.');
            }

            $oldStatus = $customer->account_status;
            $customer->forceFill(['account_status' => 'active'])->save();

            AdminAuditLogger::log(
                module: 'customers',
                action: 'activate',
                targetTable: 'users',
                targetId: $customer->id,
                oldValues: ['account_status' => $oldStatus],
                newValues: ['account_status' => 'active']
            );

            return back()->with('success', "Customer account {$customer->name} has been activated.");
        });
    }
}
