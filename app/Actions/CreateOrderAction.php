<?php

namespace App\Actions;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\StockAdjustment;
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

            // 2. Lock variant rows ordered by ID to avoid deadlocks
            $variantIds = $cart->items
                ->pluck('product_variant_id')
                ->unique()
                ->sort()
                ->values()
                ->all();

            $variants = ProductVariant::with('product')
                ->whereIn('id', $variantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // 3. Deduct stock conditionally and write stock ledger entries
            foreach ($cart->items as $item) {
                $variant = $variants->get($item->product_variant_id);

                if (!$variant) {
                    throw new Exception("Product variant was not found.");
                }

                if ($variant->trashed()) {
                    throw new Exception("Product variant (Size {$variant->size}) is no longer available.");
                }

                if (!$variant->product || $variant->product->trashed() || !$variant->product->is_active) {
                    throw new Exception("Product '{$variant->product?->name}' is currently unavailable for purchase.");
                }

                $quantityBefore = (int) $variant->stock_quantity;

                // Conditional update: only deduct if stock_quantity >= qty
                $affected = ProductVariant::where('id', $variant->id)
                    ->where('stock_quantity', '>=', $item->quantity)
                    ->decrement('stock_quantity', $item->quantity);

                if (!$affected) {
                    throw new Exception("Product {$variant->product->name} (Size {$variant->size}) is out of stock.");
                }

                $quantityAfter = $quantityBefore - (int) $item->quantity;
                $variant->stock_quantity = $quantityAfter;

                // Write stock_adjustments row per item
                StockAdjustment::create([
                    'product_variant_id' => $variant->id,
                    'order_id' => $order->id,
                    'admin_id' => null,
                    'adjustment_type' => 'order_reserved',
                    'quantity_change' => -(int) $item->quantity,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityAfter,
                    'reason' => "Order #{$order->order_number} Reserved",
                    'created_at' => now(),
                ]);

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

            // 4. Write first order_status_history row
            \App\Services\OrderStatusRecorder::record(
                $order,
                null,
                'confirmed',
                null,
                "Order #{$order->order_number} created"
            );

            // 5. Initialize Shipment record
            Shipment::create([
                'order_id' => $order->id,
                'carrier' => 'J&T Express',
                'tracking_number' => null, // Added later by Hoyos Admin
                'status' => Shipment::STATUS_ORDER_CONFIRMED,
            ]);

            // 6. Initialize Payment
            $this->paymentService->initializePayment($order);

            // 7. Empty Cart
            $cart->items()->delete();

            return $order->load(['items.variant.product', 'latestPayment', 'payments', 'shipment']);
        });
    }
}
