<?php

namespace App\Actions;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipment;
use App\Models\User;
use App\Services\PaymentService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOrderAction
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Execute the order creation from cart items and customer inputs.
     */
    public function execute(Cart $cart, array $data, ?User $user = null): Order
    {
        if ($cart->items->isEmpty()) {
            throw new Exception('Cannot create an order with an empty cart.');
        }

        return DB::transaction(function () use ($cart, $data, $user) {
            $subtotal = $cart->subtotal;
            $shippingFee = 0.00; // Free standard shipping in Cambodia promotion
            $totalAmount = $subtotal + $shippingFee;

            $guestToken = $user ? null : Str::random(40);
            $orderNumber = 'HOYOS-' . str_pad((string) (Order::max('id') + 1), 6, '0', STR_PAD_LEFT);

            // 1. Create Order
            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $user?->id,
                'guest_token' => $guestToken,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? $user?->email,
                'shipping_address' => $data['shipping_address'],
                'province' => $data['province'],
                'delivery_notes' => $data['delivery_notes'] ?? null,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total_amount' => $totalAmount,
                'order_status' => 'confirmed',
                'payment_method' => $data['payment_method'], // aba_khqr or cod
                'payment_status' => 'pending',
            ]);

            // 2. Create Order Items & Deduct Stock
            foreach ($cart->items as $item) {
                $variant = $item->variant;
                if ($variant->stock_quantity < $item->quantity) {
                    throw new Exception("Product {$variant->product->name} (Size {$variant->size}) is out of stock.");
                }

                $variant->decrement('stock_quantity', $item->quantity);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'variant_size' => $variant->size,
                    'unit_price' => $variant->effective_price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal,
                ]);
            }

            // 3. Initialize Shipment record
            Shipment::create([
                'order_id' => $order->id,
                'carrier' => 'J&T Express',
                'tracking_number' => null, // Added later by Hoyos Admin
                'status' => Shipment::STATUS_ORDER_CONFIRMED,
            ]);

            // 4. Initialize Payment
            $this->paymentService->initializePayment($order);

            // 5. Empty Cart
            $cart->items()->delete();

            return $order->load(['items.variant.product', 'payment', 'shipment']);
        });
    }
}
