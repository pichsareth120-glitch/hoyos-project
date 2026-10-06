<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use InvalidArgumentException;

class PaymentService
{
    public function __construct(
        protected AbaPaymentService $abaPaymentService
    ) {}

    /**
     * Initialize payment for an order based on chosen payment method.
     */
    public function initializePayment(Order $order): Payment
    {
        if ($order->payment_method === 'aba_khqr') {
            return $this->abaPaymentService->createKhqrPayment($order);
        }

        if ($order->payment_method === 'cod') {
            // Cash on Delivery
            return Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'payment_method' => 'cod',
                    'transaction_id' => 'COD-' . $order->order_number,
                    'amount' => $order->total_amount,
                    'currency' => 'USD',
                    'status' => 'pending', // IMPORTANT: COD remains pending until delivery
                    'expires_at' => null,
                    'paid_at' => null,
                ]
            );
        }

        throw new InvalidArgumentException("Unsupported payment method: {$order->payment_method}");
    }

    public function aba(): AbaPaymentService
    {
        return $this->abaPaymentService;
    }
}
