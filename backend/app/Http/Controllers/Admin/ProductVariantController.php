<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVariantRequest;
use App\Http\Requests\Admin\UpdateVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Services\AdminAuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductVariantController extends Controller
{
    /**
     * Store a new variant for a product.
     */
    public function store(StoreVariantRequest $request, int $productId)
    {
        $product = Product::withTrashed()->findOrFail($productId);
        $size = $request->input('size');

        // Check if this size is currently active for this product
        $activeVariant = $product->variants()->whereNull('deleted_at')->where('size', $size)->first();
        if ($activeVariant) {
            return back()->withInput()->with('error', "Size '{$size}' is already active for this product.");
        }

        // Check if this size exists in soft-deleted variants for this product
        $trashedVariant = $product->variants()->onlyTrashed()->where('size', $size)->first();
        if ($trashedVariant) {
            return back()->withInput()->with(
                'error',
                "Size '{$size}' already exists as a soft-deleted variant (SKU: {$trashedVariant->sku}). Click 'Restore Size' in the table below to reactivate it."
            );
        }

        $variant = DB::transaction(function () use ($request, $product, $size) {
            $initialStock = (int) ($request->input('initial_stock') ?? 0);

            $variant = $product->variants()->create([
                'size' => $size,
                'sku' => $request->input('sku'),
                'price_adjustment' => $request->input('price_adjustment') ?? 0.00,
                'stock_quantity' => $initialStock,
            ]);

            // Initial stock > 0 writes a stock_adjustments row
            if ($initialStock > 0) {
                StockAdjustment::create([
                    'product_variant_id' => $variant->id,
                    'order_id' => null,
                    'admin_id' => Auth::id(),
                    'adjustment_type' => 'manual_restock',
                    'quantity_change' => $initialStock,
                    'quantity_before' => 0,
                    'quantity_after' => $initialStock,
                    'reason' => 'Initial stock',
                    'created_at' => now(),
                ]);
            }

            AdminAuditLogger::log(
                'variants',
                'create',
                'product_variants',
                $variant->id,
                null,
                $variant->toArray()
            );

            return $variant;
        });

        return back()->with('success', "Size '{$variant->size}' added successfully.");
    }

    /**
     * Update an existing variant (sku and price_adjustment ONLY).
     * stock_quantity is NOT editable here.
     */
    public function update(UpdateVariantRequest $request, int $id)
    {
        $variant = ProductVariant::withTrashed()->findOrFail($id);

        DB::transaction(function () use ($request, $variant) {
            $old = $variant->only(['sku', 'price_adjustment']);

            $variant->update([
                'sku' => $request->input('sku'),
                'price_adjustment' => $request->input('price_adjustment') ?? 0.00,
            ]);

            AdminAuditLogger::log(
                'variants',
                'update',
                'product_variants',
                $variant->id,
                $old,
                $variant->only(['sku', 'price_adjustment'])
            );
        });

        return back()->with('success', "Variant '{$variant->size}' updated.");
    }

    /**
     * Remove a variant (soft-delete only).
     * Block if it is the only active size of an active product.
     */
    public function destroy(int $id)
    {
        $variant = ProductVariant::findOrFail($id);
        $product = $variant->product;

        if ($product && $product->is_active && !$product->trashed()) {
            $activeCount = ProductVariant::where('product_id', $product->id)
                ->whereNull('deleted_at')
                ->count();

            if ($activeCount <= 1) {
                return back()->with('error', "Cannot remove the only active size of an active product. Please deactivate the product first.");
            }
        }

        DB::transaction(function () use ($variant) {
            $variant->delete();

            AdminAuditLogger::log(
                'variants',
                'soft_delete',
                'product_variants',
                $variant->id,
                ['deleted_at' => null],
                ['deleted_at' => now()->toIso8601String()]
            );
        });

        return back()->with('success', "Variant '{$variant->size}' removed (soft-deleted).");
    }

    /**
     * Restore a soft-deleted variant.
     */
    public function restore(int $id)
    {
        $variant = ProductVariant::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($variant) {
            $oldDeletedAt = $variant->deleted_at?->toIso8601String() ?? (string) $variant->deleted_at;
            $variant->restore();

            AdminAuditLogger::log(
                'variants',
                'restore',
                'product_variants',
                $variant->id,
                ['deleted_at' => $oldDeletedAt],
                ['deleted_at' => null]
            );
        });

        return back()->with('success', "Variant '{$variant->size}' restored successfully.");
    }
}
