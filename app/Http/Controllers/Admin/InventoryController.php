<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustInventoryRequest;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Services\AdminAuditLogger;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display a paginated, filterable list of product variants with stock levels.
     */
    public function index(Request $request)
    {
        $query = ProductVariant::with(['product' => fn($q) => $q->withTrashed()->with(['category', 'primaryImage'])])
            ->whereNull('deleted_at')
            ->orderBy('stock_quantity', 'asc')
            ->orderBy('id', 'desc');

        $lowStockThreshold = ProductVariant::lowStockThreshold();

        // ONE grouped query computing all variant stock stats using shared threshold
        $inventoryCounts = ProductVariant::whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total_variants,
                SUM(CASE WHEN stock_quantity > ? THEN 1 ELSE 0 END) as in_stock,
                SUM(CASE WHEN stock_quantity <= ? AND stock_quantity > 0 THEN 1 ELSE 0 END) as low_stock,
                SUM(CASE WHEN stock_quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock
            ", [$lowStockThreshold, $lowStockThreshold])
            ->first();

        // Low stock filter (stock <= threshold and stock > 0)
        if ($request->boolean('low_stock') || $request->query('stock_filter') === 'low_stock' || $request->query('filter') === 'low_stock') {
            $query->lowStock($lowStockThreshold);
        }

        // Out of stock filter (stock <= 0)
        if ($request->boolean('out_of_stock') || $request->query('stock_filter') === 'out_of_stock' || $request->query('filter') === 'out_of_stock') {
            $query->outOfStock();
        }

        // In stock filter (stock > threshold)
        if ($request->boolean('in_stock') || $request->query('stock_filter') === 'in_stock' || $request->query('filter') === 'in_stock') {
            $query->where('stock_quantity', '>', $lowStockThreshold);
        }

        // Category filter
        if ($request->filled('category_id')) {
            $categoryId = $request->query('category_id');
            $query->whereHas('product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        // Search by SKU or Product Name
        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $variants = $query->paginate(20)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('admin.inventory.index', [
            'variants' => $variants,
            'categories' => $categories,
            'lowStockThreshold' => $lowStockThreshold,
            'inventoryCounts' => $inventoryCounts,
            'filters' => $request->only(['search', 'category_id', 'low_stock', 'out_of_stock', 'in_stock', 'stock_filter', 'filter']),
        ]);
    }

    /**
     * Display current stock and audit ledger history for a specific variant.
     */
    public function show(int $id)
    {
        $variant = ProductVariant::withTrashed()
            ->with([
                'product' => fn($q) => $q->withTrashed()->with(['category', 'primaryImage']),
                'stockAdjustments' => fn($q) => $q->with(['admin', 'order'])->latest('id'),
            ])
            ->findOrFail($id);

        return view('admin.inventory.show', [
            'variant' => $variant,
        ]);
    }

    /**
     * Perform a manual stock adjustment (manual_restock, manual_shrinkage, damage).
     */
    public function adjust(AdjustInventoryRequest $request, int $id)
    {
        $type = $request->input('type');
        $quantity = (int) $request->input('quantity');
        $reason = trim($request->input('reason'));

        try {
            DB::transaction(function () use ($id, $type, $quantity, $reason) {
                $variant = ProductVariant::where('id', $id)->lockForUpdate()->firstOrFail();
                $before = (int) $variant->stock_quantity;

                if ($type === 'manual_restock') {
                    $change = $quantity;
                    $after = $before + $quantity;
                } else {
                    // manual_shrinkage or damage
                    $change = -$quantity;
                    $after = $before - $quantity;
                }

                if ($after < 0) {
                    throw new DomainException("Cannot reduce stock below 0. Current stock is {$before}, attempted deduction is {$quantity}.");
                }

                $variant->stock_quantity = $after;
                $variant->save();

                // Record immutable stock adjustment ledger entry
                StockAdjustment::create([
                    'product_variant_id' => $variant->id,
                    'order_id' => null,
                    'admin_id' => Auth::id(),
                    'adjustment_type' => $type,
                    'quantity_change' => $change,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'reason' => $reason,
                    'created_at' => now(),
                ]);

                // Record admin audit log
                AdminAuditLogger::log(
                    'inventory',
                    'adjust',
                    'product_variants',
                    $variant->id,
                    ['stock_quantity' => $before],
                    [
                        'stock_quantity' => $after,
                        'adjustment_type' => $type,
                        'quantity_change' => $change,
                        'reason' => $reason,
                    ]
                );
            });

            return back()->with('success', "Stock adjusted successfully ({$type}).");
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Failed to adjust stock: ' . $e->getMessage());
        }
    }
}
