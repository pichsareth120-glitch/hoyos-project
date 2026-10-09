<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RestoreStockAction
{
    /**
     * Restore inventory stock for an order if not previously restored.
     *
     * @param Order $order
     * @param string $type 'order_cancelled' or 'payment_expired'
     * @param User|null $admin
     * @param string|null $reason
     * @return void
     */
    public function execute(Order $order, string $type = 'order_cancelled', ?User $admin = null, ?string $reason = null): void
    {
        DB::transaction(function () use ($order, $type, $admin, $reason) {
            // Lock the order row
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            // Idempotency check: if stock was already restored for this order, do nothing
            $alreadyRestored = StockAdjustment::where('order_id', $lockedOrder->id)
                ->whereIn('adjustment_type', ['order_cancelled', 'payment_expired'])
                ->exists();

            if ($alreadyRestored) {
                return;
            }

            // Eager load order items
            $lockedOrder->loadMissing('items');

            // Collect variant IDs to lock in ascending order to prevent deadlocks
            $variantIds = $lockedOrder->items
                ->pluck('product_variant_id')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();

            $variants = ProductVariant::withTrashed()
                ->whereIn('id', $variantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $defaultReason = $reason ?: match ($type) {
                'payment_expired' => "Order #{$lockedOrder->order_number} Stock Restored (Payment Expired)",
                default => "Order #{$lockedOrder->order_number} Stock Restored (Order Cancelled)",
            };

            foreach ($lockedOrder->items as $item) {
                if (empty($item->product_variant_id) || !$variants->has($item->product_variant_id)) {
                    // Skip items whose variant is NULL or no longer exists
                    continue;
                }

                $variant = $variants->get($item->product_variant_id);
                $quantityBefore = (int) $variant->stock_quantity;

                // Increment stock on database
                $variant->increment('stock_quantity', $item->quantity);
                $quantityAfter = $quantityBefore + (int) $item->quantity;
                $variant->stock_quantity = $quantityAfter;

                // Record stock adjustment ledger row
                StockAdjustment::create([
                    'product_variant_id' => $variant->id,
                    'order_id' => $lockedOrder->id,
                    'admin_id' => $admin?->id,
                    'adjustment_type' => $type,
                    'quantity_change' => (int) $item->quantity,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityAfter,
                    'reason' => $defaultReason,
                    'created_at' => now(),
                ]);
            }
        });
    }

    /**
     * Magic invoke to allow callable execution.
     */
    public function __invoke(Order $order, string $type = 'order_cancelled', ?User $admin = null, ?string $reason = null): void
    {
        $this->execute($order, $type, $admin, $reason);
    }
}
