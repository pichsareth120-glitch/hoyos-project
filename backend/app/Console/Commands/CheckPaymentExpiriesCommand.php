<?php

namespace App\Console\Commands;

use App\Actions\CancelOrderAction;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Console\Command;

class CheckPaymentExpiriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hoyos:check-payment-expiries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expired KHQR payments and cancel expired orders past the grace period';

    /**
     * Execute the console command.
     */
    public function handle(CancelOrderAction $cancelOrderAction): int
    {
        $now = now();
        $graceMinutes = (int) config('hoyos.payment_grace_minutes', 15);

        // a) Pending KHQR payments with expires_at in the past => payments.status 'expired', orders.payment_status 'expired'.
        // Order remains 'confirmed' and stock stays reserved.
        $pendingPayments = Payment::where('payment_method', 'aba_khqr')
            ->where('status', Payment::STATUS_PENDING)
            ->where('expires_at', '<=', $now)
            ->with('order')
            ->get();

        $expiredPaymentsCount = 0;
        foreach ($pendingPayments as $payment) {
            $payment->status = Payment::STATUS_EXPIRED;
            $payment->save();

            $order = $payment->order;
            if ($order && $order->payment_status === 'pending') {
                $order->payment_status = Payment::STATUS_EXPIRED;
                $order->save();
            }

            $expiredPaymentsCount++;
        }

        // b) Orders that are 'confirmed', KHQR, payment_status 'expired',
        // whose latest payment expired more than payment_grace_minutes ago => CancelOrderAction as system with stockType 'payment_expired'.
        $expiredOrders = Order::where('order_status', 'confirmed')
            ->where('payment_method', 'aba_khqr')
            ->where('payment_status', 'expired')
            ->with('latestPayment')
            ->get();

        $cancelledOrdersCount = 0;
        foreach ($expiredOrders as $order) {
            $latestPayment = $order->latestPayment;
            if (!$latestPayment || !$latestPayment->expires_at) {
                continue;
            }

            $expiredThreshold = $latestPayment->expires_at->copy()->addMinutes($graceMinutes);

            if ($now->greaterThanOrEqualTo($expiredThreshold)) {
                try {
                    $cancelOrderAction->execute(
                        order: $order,
                        admin: null, // System automated
                        reason: "Payment grace period ({$graceMinutes} min) expired",
                        stockType: 'payment_expired'
                    );
                    $cancelledOrdersCount++;
                } catch (\Throwable $e) {
                    $this->error("Failed to cancel order #{$order->order_number}: {$e->getMessage()}");
                }
            }
        }

        $this->info("Payment expiry check completed. Expired payments: {$expiredPaymentsCount}. Cancelled orders: {$cancelledOrdersCount}.");

        return self::SUCCESS;
    }
}
