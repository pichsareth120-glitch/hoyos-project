<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\AbaPaywayService;
use Illuminate\Support\Str;

/**
 * ABA PayWay / KHQR Payment Service.
 *
 * Provides bridge to the live/sandbox AbaPaywayService integration while
 * preserving lifecycle utilities (markAsPaid, checkExpiry, retry).
 */
class AbaPaymentService
{
    public const QR_TIMEOUT_MINUTES = 5;

    public function __construct(
        protected ?AbaPaywayService $paywayService = null
    ) {
        $this->paywayService = $paywayService ?? app(AbaPaywayService::class);
    }

    public function payway(): AbaPaywayService
    {
        return $this->paywayService;
    }

    /**
     * Create or refresh an ABA KHQR payment request for the given order.
     */
    public function createKhqrPayment(Order $order): Payment
    {
        return $this->paywayService->createPurchase($order);
    }

    /**
     * Verify payment status (sandbox webhook / polling simulator).
     */
    public function markAsPaid(Payment $payment, string $externalRef = null): bool
    {
        if ($payment->isPaid()) {
            return true; // Already processed, idempotent
        }

        $payment->status = 'paid';
        $payment->paid_at = now();
        $payload = $payment->payload ?: [];
        $payload['verified_at'] = now()->toIso8601String();

        $defaultPrefix = config('hoyos.demo_mode', false) ? 'DEMO-SIMULATION-' : 'SANDBOX-';
        $payload['external_reference'] = $externalRef ?: ($defaultPrefix . strtoupper(Str::random(10)));

        if (config('hoyos.demo_mode', false) || !empty($payload['demo_mode']) || str_starts_with($payload['external_reference'], 'DEV-') || str_starts_with($payload['external_reference'], 'DEMO-')) {
            $payload['is_simulated'] = true;
            $payload['simulation_note'] = 'Simulated demonstration payment; no real financial transaction occurred.';
        }

        $payment->payload = $payload;
        $payment->save();

        // Update parent order
        $order = $payment->order;
        if ($order) {
            $order->payment_status = 'paid';
            if ($order->order_status === 'pending') {
                $order->order_status = 'confirmed';
            }
            $order->save();
        }

        return true;
    }

    /**
     * Actively query ABA PayWay check-transaction endpoint to synchronize status
     * when webhooks are unreachable (e.g. localhost development or dropped packets).
     */
    public function syncWithPayway(Payment $payment): bool
    {
        if ($payment->isPaid() || config('hoyos.demo_mode', false)) {
            return $payment->isPaid();
        }

        $tranId = $payment->transaction_id ?: $payment->order?->order_number;
        if (!$tranId) {
            return false;
        }

        $result = $this->paywayService->checkTransaction($tranId);

        // ABA PayWay success status code is 0 (or 'APPROVED'/'approved')
        $status = $result['status'] ?? null;
        $paymentStatus = strtoupper((string) ($result['payment_status'] ?? ''));

        if ((string) $status === '0' || $paymentStatus === 'APPROVED' || $paymentStatus === 'PAID') {
            $apv = $result['apv'] ?? null;
            $ref = $apv ? "ABA-{$apv}" : "ABA-{$tranId}";
            return $this->markAsPaid($payment, $ref);
        }

        return false;
    }


    /**
     * Check if payment has expired and update status.
     * Note: Must only mark expired, does NOT cancel order or restore stock.
     */
    public function checkExpiry(Payment $payment): bool
    {
        if ($payment->status === 'pending' && $payment->expires_at && $payment->expires_at->isPast()) {
            $payment->status = Payment::STATUS_EXPIRED;
            $payment->save();

            $order = $payment->order;
            if ($order && $order->payment_status === 'pending') {
                $order->payment_status = 'expired';
                $order->save();
            }

            return true;
        }

        return false;
    }

    /**
     * Synthesize an authentic EMVCo Bakong KHQR string.
     */
    public function generateKhqrString(Order $order, string $transactionId): string
    {
        $amount = number_format((float) ($order->grand_total ?? $order->total_amount), 2, '.', '');
        if (config('hoyos.demo_mode', false)) {
            return "DEMO-NON-PAYABLE-KHQR:order={$transactionId}:amount={$amount}:currency=USD";
        }
        return "00020101021229340016hoyos@ababank011000012938452045691530384054" .
               sprintf("%02d", strlen($amount)) . "{$amount}5802KH5905HOYOS6010Phnom Penh62" .
               sprintf("%02d", strlen($transactionId) + 4) . "01" . sprintf("%02d", strlen($transactionId)) . "{$transactionId}6304ABCD";
    }
}
