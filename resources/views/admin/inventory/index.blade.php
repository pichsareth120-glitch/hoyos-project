@extends('layouts.admin')

@section('title', 'Inventory Stock Levels')

@section('content')
<div class="space-y-6">
    <!-- 1. PageHeader Section -->
    <x-ui.page-header
        eyebrow="Warehouse & Stock Control"
        title="Inventory Stock Levels"
        subtitle="Track live sizing stock, review stock deduction ledgers, and log manual restocking or shrinkage."
    >
        <x-slot:status>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-mono bg-zinc-900/90 border border-white/[0.08] text-zinc-300">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                <span>Low stock alert threshold: <strong class="text-white font-mono">≤ {{ $lowStockThreshold ?? 5 }} units</strong></span>
            </span>
        </x-slot:status>
    </x-ui.page-header>

    @php
        $totalVariantsCount = (int) ($inventoryCounts->total_variants ?? 0);
        $inStockCount = (int) ($inventoryCounts->in_stock ?? 0);
        $lowStockCount = (int) ($inventoryCounts->low_stock ?? 0);
        $outOfStockCount = (int) ($inventoryCounts->out_of_stock ?? 0);

        $hasActiveFilters = request()->hasAny(['search', 'category_id', 'low_stock', 'out_of_stock', 'in_stock', 'stock_filter', 'filter']);
    @endphp

    <!-- 2. KPI StatCard Row (2 cols on mobile <640px, 4 cols on desktop) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- 1. Total Variants -->
        <a href="{{ route('admin.inventory.index') }}" class="group block h-full">
            <x-ui.stat-card
                title="Total Variants"
                :value="$totalVariantsCount"
                description="All active SKU size combinations"
                class="h-full group-hover:border-white/[0.2] transition-all cursor-pointer"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>
        </a>

        <!-- 2. In Stock -->
        <a href="{{ route('admin.inventory.index', ['stock_filter' => 'in_stock']) }}" class="group block h-full">
            <x-ui.stat-card
                title="In Stock"
                :value="$inStockCount"
                description="Healthy stock (> {{ $lowStockThreshold ?? 5 }} units)"
                class="h-full group-hover:border-emerald-500/30 transition-all cursor-pointer"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>
        </a>

        <!-- 3. Low Stock -->
        <a href="{{ route('admin.inventory.index', ['stock_filter' => 'low_stock']) }}" class="group block h-full">
            <x-ui.stat-card
                title="Low Stock"
                :value="$lowStockCount"
                description="Variants at or below alert threshold"
                class="h-full group-hover:border-amber-500/30 transition-all cursor-pointer"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>
        </a>

        <!-- 4. Out of Stock -->
        <a href="{{ route('admin.inventory.index', ['stock_filter' => 'out_of_stock']) }}" class="group block h-full">
            <x-ui.stat-card
                title="Out of Stock"
                :value="$outOfStockCount"
                description="Variants completely depleted (0 units)"
                class="h-full group-hover:border-hoyos-red/30 transition-all cursor-pointer"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-hoyos-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>
        </a>
    </div>

    <!-- 3. FilterBar Section -->
    <x-ui.filter-bar
        :action="route('admin.inventory.index')"
        method="GET"
        :search="request('search')"
        searchPlaceholder="Search product name or SKU..."
        :hasActiveFilters="$hasActiveFilters"
        :resetUrl="route('admin.inventory.index')"
    >
        <!-- Category Select -->
        <div class="w-full sm:w-auto min-w-[170px] shrink-0">
            <select 
                name="category_id" 
                class="w-full py-2 px-3 text-xs rounded-xl bg-white/[0.03] border border-white/[0.1] text-zinc-300 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all font-mono"
            >
                <option value="" class="bg-zinc-900 text-zinc-300">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Low Stock Only Checkbox -->
        <label class="flex items-center gap-2 p-2 px-3 rounded-xl bg-white/[0.03] border border-white/[0.1] hover:border-white/[0.2] cursor-pointer transition-colors text-xs shrink-0 select-none">
            <input 
                type="checkbox" 
                name="low_stock" 
                value="1" 
                {{ (request('low_stock') || request('stock_filter') === 'low_stock' || request('filter') === 'low_stock') ? 'checked' : '' }}
                class="w-3.5 h-3.5 rounded bg-zinc-900 border-zinc-700 text-amber-500 focus:ring-0 cursor-pointer"
            />
            <span class="text-zinc-300 font-mono text-[11px]">Low Stock Only</span>
        </label>

        <!-- Out of Stock Checkbox -->
        <label class="flex items-center gap-2 p-2 px-3 rounded-xl bg-white/[0.03] border border-white/[0.1] hover:border-white/[0.2] cursor-pointer transition-colors text-xs shrink-0 select-none">
            <input 
                type="checkbox" 
                name="out_of_stock" 
                value="1" 
                {{ (request('out_of_stock') || request('stock_filter') === 'out_of_stock' || request('filter') === 'out_of_stock') ? 'checked' : '' }}
                class="w-3.5 h-3.5 rounded bg-zinc-900 border-zinc-700 text-rose-500 focus:ring-0 cursor-pointer"
            />
            <span class="text-zinc-300 font-mono text-[11px]">Out of Stock</span>
        </label>
    </x-ui.filter-bar>

    <!-- 4. DataTable Container with single Pagination footer -->
    <x-ui.data-table
        :empty="$variants->isEmpty()"
        emptyTitle="No Inventory Records Found"
        emptyDescription="There are no product variants matching your current filter keywords."
        emptyActionText="Clear Filter Criteria"
        :emptyActionHref="route('admin.inventory.index')"
    >
        <x-slot:thead>
            <tr>
                <th class="px-6 py-3.5 font-sans">PRODUCT</th>
                <th class="px-4 py-3.5 font-sans">SIZE</th>
                <th class="px-4 py-3.5 font-sans">SKU</th>
                <th class="px-4 py-3.5 font-sans">CATEGORY</th>
                <th class="px-4 py-3.5 font-sans text-center">CURRENT STOCK</th>
                <th class="px-4 py-3.5 font-sans text-center">STATUS</th>
                <th class="px-6 py-3.5 font-sans text-right">ACTIONS</th>
            </tr>
        </x-slot:thead>

        @foreach($variants as $variant)
            @php
                $product = $variant->product;
                $primaryImg = $product?->primaryImage 
                    ? asset($product->primaryImage->image_path) 
                    : asset('images/hoyos/0ffd9c02726576625184fe52b38ace6d.jpg');
                $stock = $variant->stock_quantity;
                $statusKey = $stock <= 0 ? 'out_of_stock' : ($stock <= ($lowStockThreshold ?? 5) ? 'low_stock' : 'in_stock');
                $statusLabel = $stock <= 0 ? 'Out of Stock' : ($stock <= ($lowStockThreshold ?? 5) ? 'Low Stock' : 'In Stock');
            @endphp
            <tr class="hover:bg-white/[0.02] transition-colors">
                <!-- 1. PRODUCT & THUMBNAIL -->
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl overflow-hidden bg-zinc-900 border border-white/[0.08] shrink-0">
                            <img 
                                src="{{ $primaryImg }}" 
                                alt="{{ $product?->name ?? 'Product' }}"
                                class="w-full h-full object-cover"
                                loading="lazy"
                            />
                        </div>
                        <div class="min-w-0">
                            @if($product)
                                <a 
                                    href="{{ route('admin.products.edit', $product) }}" 
                                    class="font-sans font-medium text-white text-xs hover:text-hoyos-red transition-colors block truncate"
                                >
                                    {{ $product->name }}
                                </a>
                                <span class="text-[10px] font-mono text-admin-muted block mt-0.5">
                                    ${{ number_format($variant->effective_price, 2) }}
                                </span>
                            @else
                                <span class="text-admin-muted italic text-xs font-sans">Orphan Variant</span>
                            @endif
                        </div>
                    </div>
                </td>

                <!-- 2. SIZE -->
                <td class="px-4 py-4 font-mono font-bold text-white text-xs">
                    {{ $variant->size }}
                </td>

                <!-- 3. SKU -->
                <td class="px-4 py-4 font-mono text-zinc-300 text-xs">
                    {{ $variant->sku }}
                </td>

                <!-- 4. CATEGORY -->
                <td class="px-4 py-4 font-sans text-xs text-zinc-300">
                    <span class="font-mono text-xs text-zinc-300 bg-white/[0.04] border border-white/[0.08] px-2 py-0.5 rounded-lg inline-block">
                        {{ $product?->category?->name ?? '—' }}
                    </span>
                </td>

                <!-- 5. CURRENT STOCK -->
                <td class="px-4 py-4 text-center">
                    <span class="tabular-nums font-mono text-sm text-white font-bold">
                        {{ $stock }}
                    </span>
                </td>

                <!-- 6. STATUS -->
                <td class="px-4 py-4 text-center">
                    <x-ui.status-badge :status="$statusKey" :label="$statusLabel" size="sm" />
                </td>

                <!-- 7. ACTIONS -->
                <td class="px-6 py-4 text-right">
                    <x-ui.button 
                        :href="route('admin.inventory.show', $variant)" 
                        variant="secondary"
                        size="xs"
                        class="font-sans"
                    >
                        <span>Adjust / Ledger</span>
                        <span>&rarr;</span>
                    </x-ui.button>
                </td>
            </tr>
        @endforeach

        <x-slot:footer>
            <x-ui.pagination :paginator="$variants" />
        </x-slot:footer>
    </x-ui.data-table>
</div>
@endsection
