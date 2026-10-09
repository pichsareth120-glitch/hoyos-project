<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CancelOrderAction;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\AdminAuditLogger;
use App\Services\OrderStatusRecorder;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a paginated, filterable list of orders.
     */
    public function index(Request $request)
    {
        $query = Order::with(['shipment', 'latestPayment', 'items.variant.product'])
            ->latest();

        // 0. Dedicated queue filters from Dashboard
        $queue = $request->query('queue');
        if ($queue === 'awaiting_processing') {
            $query->where('order_status', 'confirmed')
                ->where(function ($q) {
                    $q->where('payment_method', 'cod')
                      ->orWhere('payment_status', 'paid');
                });
        } elseif ($queue === 'khqr_awaiting_payment') {
            $query->where('payment_method', 'aba_khqr')
                ->where('order_status', 'confirmed')
                ->whereIn('payment_status', ['pending', 'expired']);
        } elseif ($queue === 'cod_awaiting_cash') {
            $query->where('payment_method', 'cod')
                ->whereIn('order_status', ['shipped', 'delivered'])
                ->where('payment_status', 'pending');
        } elseif ($queue === 'refunds_due') {
            $query->where('order_status', 'cancelled')
                ->where('payment_status', 'paid');
        } else {
            // 1. Filter by order_status
            if ($request->filled('order_status')) {
                $query->where('order_status', $request->query('order_status'));
            }

            // 2. Filter by payment_status
            if ($request->filled('payment_status')) {
                $query->where('payment_status', $request->query('payment_status'));
            }

            // 3. Filter by payment_method (supports both payment_method and method)
            $method = $request->query('payment_method') ?? $request->query('method');
            if (!empty($method)) {
                $query->where('payment_method', $method);
            }
        }

        // 4. Search across order number, customer name, customer phone
        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        // 5. Date Range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->query('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->query('date_to'));
        }

        $orders = $query->paginate(20)->withQueryString();

        // Grouped pipeline KPI counts in a single query (no N+1, conditional counts)
        $pipelineCounts = Order::selectRaw("
            COUNT(CASE WHEN order_status = 'processing' THEN 1 END) as awaiting_processing,
            COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as awaiting_payment,
            COUNT(CASE WHEN order_status = 'shipped' THEN 1 END) as in_transit,
            COUNT(CASE WHEN order_status = 'delivered' THEN 1 END) as delivered
        ")->first();

        return view('admin.orders.index', [
            'orders' => $orders,
            'pipelineCounts' => $pipelineCounts,
            'filters' => $request->only([
                'order_status',
                'payment_status',
                'payment_method',
                'method',
                'search',
                'date_from',
                'date_to',
            ]),
        ]);
    }

    /**
     * Display detailed order view.
     */
    public function show(Order $order)
    {
        $order->load([
            'user',
            'items.variant.product',
            'payments' => fn($q) => $q->latest(),
            'shipment',
            'orderStatusHistories.admin',
            'stockAdjustments.variant.product',
            'stockAdjustments.admin',
        ]);

        return view('admin.orders.show', [
            'order' => $order,
        ]);
    }

    /**
     * Display a paginated, filterable list of customer sales invoices & receipts.
     */
    public function invoices(Request $request)
    {
        $query = Order::with(['user', 'latestPayment', 'payments', 'shipment', 'items'])
            ->latest();

        // 1. Unified Search (q or search) across order ID, order number, customer name, email, phone
        $search = trim($request->query('q') ?? $request->query('search') ?? '');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }
                $q->orWhere('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        // 2. Filter by payment_status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->query('payment_status'));
        }

        // 3. Filter by delivery carrier
        if ($request->filled('carrier')) {
            $carrier = trim($request->query('carrier'));
            $query->whereHas('shipment', function ($q) use ($carrier) {
                $q->where('carrier', 'like', "%{$carrier}%");
            });
        }

        // 4. Date Range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->query('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->query('date_to'));
        }

        // Available carriers for dropdown filter
        $availableCarriers = Shipment::query()
            ->whereNotNull('carrier')
            ->where('carrier', '!=', '')
            ->distinct()
            ->pluck('carrier')
            ->toArray();

        if (empty($availableCarriers)) {
            $availableCarriers = ['J&T Express', 'Virak Buntham', 'Standard Delivery'];
        }

        $orders = $query->paginate(15)->withQueryString();

        // Grouped invoice KPI counts in a single query (no N+1, conditional counts)
        $invoiceCounts = Order::selectRaw("
            COUNT(*) as total_invoices,
            COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_invoices,
            COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_invoices,
            COUNT(CASE WHEN payment_status = 'refunded' THEN 1 END) as refunded_invoices
        ")->first();

        return view('admin.invoices.index', [
            'orders' => $orders,
            'invoiceCounts' => $invoiceCounts,
            'carriers' => $availableCarriers,
            'availableCarriers' => $availableCarriers,
            'filters' => [
                'search' => $search,
                'payment_status' => $request->query('payment_status', ''),
                'carrier' => $request->query('carrier', ''),
                'date_from' => $request->query('date_from', ''),
                'date_to' => $request->query('date_to', ''),
            ],
        ]);
    }

    /**
     * Display printable customer sales invoice & receipt view.
     */
    public function invoiceShow(Order $order)
    {
        $order->load([
            'user',
            'items.variant.product',
            'payments' => fn($q) => $q->latest(),
            'shipment',
            'orderStatusHistories.admin',
        ]);

        return view('admin.invoices.show', [
            'order' => $order,
        ]);
    }

    /**
     * Advance order status from confirmed to processing.
     * Enforces: confirmed -> processing only; KHQR requires payment_status 'paid'; COD allowed.
     */
    public function processing(Order $order)
    {
        try {
            DB::transaction(function () use ($order) {
                $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

                if ($lockedOrder->order_status !== 'confirmed') {
                    throw new DomainException("Order #{$lockedOrder->order_number} cannot be moved to processing. Current status is '{$lockedOrder->order_status}'.");
                }

                if (!$lockedOrder->isCod() && !$lockedOrder->isPaid()) {
                    throw new DomainException("KHQR Order #{$lockedOrder->order_number} requires payment before processing (current payment status: '{$lockedOrder->payment_status}').");
                }

                $oldStatus = $lockedOrder->order_status;
                $lockedOrder->order_status = 'processing';
                $lockedOrder->save();

                // Update shipment to processing if still in order_confirmed
                if ($shipment = $lockedOrder->shipment) {
                    if ($shipment->status === Shipment::STATUS_ORDER_CONFIRMED) {
                        $shipment->status = Shipment::STATUS_PROCESSING;
                        $shipment->save();
                    }
                }

                // Record status history
                OrderStatusRecorder::record(
                    $lockedOrder,
                    $oldStatus,
                    'processing',
                    Auth::id(),
                    'Order moved to processing by admin'
                );

                // Record admin audit log
                AdminAuditLogger::log(
                    'orders',
                    'update_status',
                    'orders',
                    $lockedOrder->id,
                    ['order_status' => $oldStatus],
                    ['order_status' => 'processing']
                );
            });

            return back()->with('success', "Order #{$order->order_number} status updated to Processing.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to update order status: ' . $e->getMessage());
        }
    }

    /**
     * Cancel an active order and restore stock.
     */
    public function cancel(Request $request, Order $order, CancelOrderAction $cancelOrderAction)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        try {
            $cancelOrderAction->execute(
                $order,
                $request->user(),
                $validated['reason'],
                'order_cancelled'
            );

            return back()->with('success', "Order #{$order->order_number} has been cancelled and inventory reserved stock restored.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to cancel order: ' . $e->getMessage());
        }
    }
}
