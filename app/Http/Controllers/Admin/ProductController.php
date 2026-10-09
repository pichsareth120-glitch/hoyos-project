<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\AdminAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a paginated, filterable list of products.
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'primaryImage'])
            ->withSum(['variants as total_stock' => function ($q) {
                $q->whereNull('deleted_at');
            }], 'stock_quantity')
            ->latest('id');

        // Trashed filter
        if ($request->query('trashed') === 'only') {
            $query->onlyTrashed();
        } elseif ($request->query('trashed') === 'with') {
            $query->withTrashed();
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        // Active status filter
        if ($request->filled('status')) {
            $status = $request->query('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search name or slug
        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $products = $query->paginate(20)->withQueryString();
        $categories = Category::orderBy('name')->get();
        $lowStockThreshold = ProductVariant::lowStockThreshold();

        $activeCount = Product::whereNull('deleted_at')->where('is_active', true)->count();
        $draftCount = Product::whereNull('deleted_at')->where('is_active', false)->count();
        $totalCount = Product::whereNull('deleted_at')->count();
        $lowStockCount = ProductVariant::whereNull('deleted_at')->lowStock($lowStockThreshold)->count();
        $outOfStockCount = ProductVariant::whereNull('deleted_at')->outOfStock()->count();

        $productCounts = (object) [
            'active_count' => $activeCount,
            'draft_count' => $draftCount,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'total_count' => $totalCount,
        ];

        $hasActiveFilters = $request->filled('search') 
            || $request->filled('category_id') 
            || $request->filled('status') 
            || $request->filled('trashed');

        return view('admin.products.index', [
            'products' => $products,
            'categories' => $categories,
            'productCounts' => $productCounts,
            'lowStockThreshold' => $lowStockThreshold,
            'hasActiveFilters' => $hasActiveFilters,
            'filters' => $request->only(['search', 'category_id', 'status', 'trashed']),
        ]);
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.products.create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $product = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['is_active'] = $request->boolean('is_active');
            $data['is_featured'] = $request->boolean('is_featured');
            $data['is_new_arrival'] = $request->boolean('is_new_arrival');

            $product = Product::create($data);

            AdminAuditLogger::log(
                'products',
                'create',
                'products',
                $product->id,
                null,
                $product->toArray()
            );

            return $product;
        });

        return redirect()->route('admin.products.edit', $product)
            ->with('success', "Product '{$product->name}' created successfully. You can now add size variants and images.");
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(int $id)
    {
        $product = Product::withTrashed()->with([
            'category',
            'variants' => fn($q) => $q->withTrashed(),
            'images' => fn($q) => $q->orderBy('display_order')->orderBy('id'),
        ])->findOrFail($id);

        $product->variants->each(function ($variant) use ($product) {
            $variant->setRelation('product', $product);
        });

        $categories = Category::orderBy('name')->get();
        $allowedSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'One size'];

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => $categories,
            'allowedSizes' => $allowedSizes,
        ]);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateProductRequest $request, int $id)
    {
        $product = Product::withTrashed()->findOrFail($id);

        DB::transaction(function () use ($request, $product) {
            $oldValues = $product->getOriginal();
            $priceChanged = (float) $product->price !== (float) $request->input('price');

            $data = $request->validated();
            $data['is_active'] = $request->boolean('is_active');
            $data['is_featured'] = $request->boolean('is_featured');
            $data['is_new_arrival'] = $request->boolean('is_new_arrival');

            $product->update($data);

            $changes = $product->getChanges();
            unset($changes['updated_at']);

            if (!empty($changes)) {
                $changedKeys = array_keys($changes);
                $filteredOld = array_intersect_key($oldValues, array_flip($changedKeys));

                if ($priceChanged) {
                    $changes['note'] = 'Price changes do not alter historical orders (order_items hold immutable snapshots)';
                }

                AdminAuditLogger::log(
                    'products',
                    'update',
                    'products',
                    $product->id,
                    $filteredOld,
                    $changes
                );
            }
        });

        return redirect()->route('admin.products.edit', $product)
            ->with('success', "Product '{$product->name}' updated successfully.");
    }

    /**
     * Toggle the active state of a product.
     */
    public function toggleActive(int $id)
    {
        $product = Product::withTrashed()->findOrFail($id);

        DB::transaction(function () use ($product) {
            $old = ['is_active' => $product->is_active];
            $product->is_active = !$product->is_active;
            $product->save();

            AdminAuditLogger::log(
                'products',
                'toggle_active',
                'products',
                $product->id,
                $old,
                ['is_active' => $product->is_active]
            );
        });

        $statusStr = $product->is_active ? 'active' : 'inactive';
        return back()->with('success', "Product '{$product->name}' is now {$statusStr}.");
    }

    /**
     * Soft delete the specified product.
     */
    public function destroy(int $id)
    {
        $product = Product::findOrFail($id);

        DB::transaction(function () use ($product) {
            $product->delete();

            AdminAuditLogger::log(
                'products',
                'soft_delete',
                'products',
                $product->id,
                ['deleted_at' => null],
                ['deleted_at' => now()->toIso8601String()]
            );
        });

        return back()->with('success', "Product '{$product->name}' has been soft-deleted.");
    }

    /**
     * Restore a soft-deleted product.
     */
    public function restore(int $id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($product) {
            $oldDeletedAt = $product->deleted_at?->toIso8601String() ?? (string) $product->deleted_at;
            $product->restore();

            AdminAuditLogger::log(
                'products',
                'restore',
                'products',
                $product->id,
                ['deleted_at' => $oldDeletedAt],
                ['deleted_at' => null]
            );
        });

        return back()->with('success', "Product '{$product->name}' restored successfully.");
    }
}
