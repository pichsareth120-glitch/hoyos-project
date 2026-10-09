<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\AdminAuditLogger;
use App\Services\OrderStatusRecorder;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Display a paginated list of payments with filtering.
     */
    public function index(Request $request)
    {
        $query = Payment::with(['order.shipment'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('method')) {
            $query->where('payment_method', $request->query('method'));
        }

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($oq) use ($search) {
                        $oq->where('order_number', 'like', "%{$search}%")
                            ->orWhere('customer_name', 'like', "%{$search}%");
                    });
            });
        }

        $payments = $query->paginate(20)->withQueryString();

        // Single grouped count query for real KPI counts (zero N+1)
        $paymentCounts = Payment::selectRaw("
            COUNT(*) as total_payments,
            COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN status = 'refunded' THEN 1 END) as refunded_count,
            COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as paid_volume
        ")->first();

        return view('admin.payments.index', [
            'payments' => $payments,
            'filters' => $request->only(['status', 'method', 'search']),
            'paymentCounts' => $paymentCounts,
        ]);
    }

    /**
     * Mark a pending COD payment as paid once order has shipped/delivered.
     */
    public function markPaid(Payment $payment)
    {
        try {
            DB::transaction(function () use ($payment) {
                $lockedPayment = Payment::where('id', $payment->id)->lockForUpdate()->firstOrFail();
                $lockedOrder = Order::where('id', $lockedPayment->order_id)->lockForUpdate()->firstOrFail();

                if ($lockedPayment->payment_method !== 'cod') {
                    throw new DomainException("Only Cash on Delivery (COD) payments can be manually marked as paid.");
                }

                if ($lockedPayment->status !== Payment::STATUS_PENDING) {
                    throw new DomainException("Payment #{$lockedPayment->id} cannot be marked paid. Current payment status is '{$lockedPayment->status}'.");
                }

                if ($lockedOrder->order_status === 'cancelled') {
                    throw new DomainException("Cannot mark payment as paid for a cancelled order.");
                }

                if (!in_array($lockedOrder->order_status, ['shipped', 'delivered'], true)) {
                    throw new DomainException("COD payments can only be marked paid after the order is shipped or delivered (current status: '{$lockedOrder->order_status}').");
                }

                $oldPaymentStatus = $lockedPayment->status;
                $oldOrderPaymentStatus = $lockedOrder->payment_status;

                $lockedPayment->status = Payment::STATUS_PAID;
                $lockedPayment->paid_at = now();
                $lockedPayment->save();

                $lockedOrder->payment_status = Payment::STATUS_PAID;
                $lockedOrder->save();

                // Order status history entry
                OrderStatusRecorder::record(
                    $lockedOrder,
                    $lockedOrder->order_status,
                    $lockedOrder->order_status,
                    Auth::id(),
                    "COD Payment #{$lockedPayment->id} marked as paid upon delivery by admin"
                );

                // Admin audit logs
                AdminAuditLogger::log(
                    'payments',
                    'mark_paid',
                    'payments',
                    $lockedPayment->id,
                    ['status' => $oldPaymentStatus],
                    ['status' => Payment::STATUS_PAID]
                );

                AdminAuditLogger::log(
                    'payments',
                    'mark_paid',
                    'orders',
                    $lockedOrder->id,
                    ['payment_status' => $oldOrderPaymentStatus],
                    ['payment_status' => Payment::STATUS_PAID]
                );
            });

            return back()->with('success', "Payment #{$payment->id} successfully marked as Paid.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to mark payment as paid: ' . $e->getMessage());
        }
    }

    /**
     * Record an off-platform refund for a paid payment.
     * Note: This only records the refund in system records. It does NOT cancel the order or alter inventory stock.
     */
    public function refund(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'notes' => 'required|string|min:5|max:500',
        ]);

        try {
            DB::transaction(function () use ($payment, $validated) {
                $lockedPayment = Payment::where('id', $payment->id)->lockForUpdate()->firstOrFail();
                $lockedOrder = Order::where('id', $lockedPayment->order_id)->lockForUpdate()->firstOrFail();

                if ($lockedPayment->status !== Payment::STATUS_PAID) {
                    throw new DomainException("Only payments with status 'paid' can be refunded (current status: '{$lockedPayment->status}').");
                }

                $oldPaymentStatus = $lockedPayment->status;
                $oldOrderPaymentStatus = $lockedOrder->payment_status;

                $lockedPayment->status = Payment::STATUS_REFUNDED;
                $lockedPayment->save();

                $lockedOrder->payment_status = Payment::STATUS_REFUNDED;
                $lockedOrder->save();

                // Order status history entry with notes
                OrderStatusRecorder::record(
                    $lockedOrder,
                    $lockedOrder->order_status,
                    $lockedOrder->order_status,
                    Auth::id(),
                    "Off-platform refund recorded: {$validated['notes']}"
                );

                // Admin audit logs
                AdminAuditLogger::log(
                    'payments',
                    'refund',
                    'payments',
                    $lockedPayment->id,
                    ['status' => $oldPaymentStatus],
                    ['status' => Payment::STATUS_REFUNDED, 'notes' => $validated['notes']]
                );

                AdminAuditLogger::log(
                    'payments',
                    'refund',
                    'orders',
                    $lockedOrder->id,
                    ['payment_status' => $oldOrderPaymentStatus],
                    ['payment_status' => Payment::STATUS_REFUNDED, 'notes' => $validated['notes']]
                );
            });

            return back()->with('success', "Off-platform refund recorded for payment #{$payment->id}.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to process refund: ' . $e->getMessage());
        }
    }
}
