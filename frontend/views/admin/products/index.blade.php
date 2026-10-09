@extends('layouts.admin')

@section('title', 'Streetwear Products')

@section('content')
<div 
    x-data="{
        archiveModalOpen: false,
        archiveActionUrl: '',
        archiveProductName: '',
        openArchiveModal(url, name) {
            this.archiveActionUrl = url;
            this.archiveProductName = name;
            this.archiveModalOpen = true;
        }
    }" 
    class="space-y-6"
>
    <!-- Page Header -->
    <x-ui.page-header
        eyebrow="Catalog Management"
        title="Streetwear Products"
        subtitle="Manage apparel listings, pricing models, sizing variants, and visual lookbooks."
    >
        <x-slot:actions>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-mono bg-zinc-900/90 border border-white/[0.08] text-zinc-300">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Total: <strong class="text-white font-mono">{{ $products->total() ?? count($products) }}</strong> products</span>
            </span>
            <x-ui.button 
                :href="route('admin.products.create')" 
                variant="primary"
                size="sm"
                class="font-sans font-semibold tracking-wide"
            >
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>New Product</span>
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <!-- KPI Stat Row (One Grouped Query, Equal Heights) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- 1. Active Listings -->
        <a href="{{ route('admin.products.index', ['status' => 'active']) }}" class="group block h-full">
            <x-ui.stat-card
                title="Active Listings"
                :value="$productCounts->active_count ?? 0"
                subtext="Live on storefront"
                accent="green"
                class="h-full group-hover:border-emerald-500/30 transition-all cursor-pointer"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>
        </a>

        <!-- 2. Draft Catalog -->
        <a href="{{ route('admin.products.index', ['status' => 'inactive']) }}" class="group block h-full">
            <x-ui.stat-card
                title="Draft Catalog"
                :value="$productCounts->draft_count ?? 0"
                subtext="Unpublished designs"
                accent="neutral"
                class="h-full group-hover:border-zinc-500/30 transition-all cursor-pointer"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>
        </a>

        <!-- 3. Low Stock Alerts -->
        <a href="{{ route('admin.inventory.index', ['stock_filter' => 'low_stock']) }}" class="group block h-full">
            <x-ui.stat-card
                title="Low Stock Alerts"
                :value="$productCounts->low_stock_count ?? 0"
                subtext="Variants ≤ {{ $lowStockThreshold ?? 5 }} units"
                accent="amber"
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
                :value="$productCounts->out_of_stock_count ?? 0"
                subtext="Depleted variant inventory"
                accent="red"
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

    <!-- FilterBar Section (Clean wrapping at 1280px) -->
    <x-ui.filter-bar
        :action="route('admin.products.index')"
        method="GET"
        :search="request('search')"
        searchPlaceholder="Search product name or slug..."
        :hasActiveFilters="$hasActiveFilters"
        :resetUrl="route('admin.products.index')"
    >
        <!-- Category Select -->
        <div class="w-full sm:w-auto min-w-[160px] shrink-0">
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

        <!-- Status Select -->
        <div class="w-full sm:w-auto min-w-[140px] shrink-0">
            <select 
                name="status" 
                class="w-full py-2 px-3 text-xs rounded-xl bg-white/[0.03] border border-white/[0.1] text-zinc-300 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all font-mono"
            >
                <option value="" class="bg-zinc-900 text-zinc-300">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Active Only</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Draft Only</option>
            </select>
        </div>

        <!-- Archive State Select -->
        <div class="w-full sm:w-auto min-w-[170px] shrink-0">
            <select 
                name="trashed" 
                class="w-full py-2 px-3 text-xs rounded-xl bg-white/[0.03] border border-white/[0.1] text-zinc-300 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all font-mono"
            >
                <option value="" {{ empty(request('trashed')) ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Without Trashed</option>
                <option value="with" {{ request('trashed') === 'with' ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">All (Incl. Trashed)</option>
                <option value="only" {{ request('trashed') === 'only' ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Trashed Only</option>
            </select>
        </div>
    </x-ui.filter-bar>

    <!-- Products Catalog DataTable Container -->
    <x-ui.data-table
        :empty="$products->isEmpty()"
        emptyTitle="No Products Found"
        emptyDescription="There are no streetwear products matching your current query parameters."
        emptyActionText="Clear Filter Criteria"
        :emptyActionHref="route('admin.products.index')"
    >
        <x-slot:thead>
            <tr>
                <th class="px-6 py-3.5 font-sans">PRODUCT</th>
                <th class="px-4 py-3.5 font-sans">CATEGORY</th>
                <th class="px-4 py-3.5 text-right font-mono">PRICE</th>
                <th class="px-4 py-3.5 text-center font-sans">TOTAL STOCK</th>
                <th class="px-4 py-3.5 text-center font-sans">STATUS</th>
                <th class="px-6 py-3.5 text-right font-sans">ACTIONS</th>
            </tr>
        </x-slot:thead>

        @foreach($products as $product)
            @php
                $primaryImg = $product->primaryImage 
                    ? asset($product->primaryImage->image_path) 
                    : asset('images/hoyos/0ffd9c02726576625184fe52b38ace6d.jpg');
                $isTrashed = $product->trashed();
                $totalStock = (int) ($product->total_stock ?? 0);
            @endphp
            <tr class="hover:bg-white/[0.02] transition-colors border-b border-white/[0.04] last:border-b-0 {{ $isTrashed ? 'opacity-60 bg-red-950/10' : '' }}">
                <!-- 1. PRODUCT -->
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <img 
                            src="{{ $primaryImg }}" 
                            alt="{{ $product->name }}"
                            class="w-10 h-10 rounded-lg border border-white/[0.08] object-cover bg-zinc-900 shrink-0"
                            loading="lazy"
                        />
                        <div class="min-w-0">
                            <a 
                                href="{{ route('admin.products.edit', $product) }}" 
                                class="text-white font-medium text-xs hover:text-hoyos-red transition-colors block truncate font-sans"
                            >
                                {{ $product->name }}
                            </a>
                            <div class="text-admin-muted font-mono text-[11px] block mt-0.5 truncate select-all">
                                /product/{{ $product->slug }}
                            </div>
                            <div class="flex items-center gap-1.5 mt-1">
                                @if($product->is_featured)
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-mono font-medium bg-amber-500/10 text-amber-300 border border-amber-500/20">
                                        FEATURED
                                    </span>
                                @endif
                                @if($product->is_new_arrival)
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-mono font-medium bg-hoyos-red/10 text-hoyos-red border border-hoyos-red/20">
                                        NEW
                                    </span>
                                @endif
                                @if($isTrashed)
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-mono font-medium bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                        ARCHIVED
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </td>

                <!-- 2. CATEGORY -->
                <td class="px-4 py-4">
                    <span class="font-sans text-xs text-zinc-300 bg-white/[0.03] border border-white/[0.08] px-2.5 py-1 rounded-lg inline-block">
                        {{ $product->category?->name ?? 'Uncategorized' }}
                    </span>
                </td>

                <!-- 3. PRICE -->
                <td class="px-4 py-4 text-right font-mono">
                    <div class="font-bold text-white text-xs tabular-nums">
                        ${{ number_format($product->price, 2) }}
                    </div>
                    @if($product->compare_at_price)
                        <div class="text-[10px] text-admin-muted line-through tabular-nums mt-0.5">
                            ${{ number_format($product->compare_at_price, 2) }}
                        </div>
                    @endif
                </td>

                <!-- 4. TOTAL STOCK -->
                <td class="px-4 py-4 text-center">
                    @if($totalStock <= 0)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-mono bg-rose-500/10 text-rose-400 border border-rose-500/20 whitespace-nowrap">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                            0 units
                        </span>
                    @elseif($totalStock <= ($lowStockThreshold ?? 5))
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-mono bg-amber-500/10 text-amber-300 border border-amber-500/20 whitespace-nowrap">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                            {{ $totalStock }} units
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-mono bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 whitespace-nowrap">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            {{ $totalStock }} units
                        </span>
                    @endif
                </td>

                <!-- 5. STATUS -->
                <td class="px-4 py-4 text-center">
                    @if(!$isTrashed)
                        <form method="POST" action="{{ route('admin.products.toggle-active', $product) }}" class="inline">
                            @csrf
                            <button 
                                type="submit" 
                                title="Click to toggle status"
                                class="cursor-pointer transition-transform active:scale-95"
                            >
                                <x-ui.status-badge :status="$product->is_active ? 'active' : 'draft'" size="sm" />
                            </button>
                        </form>
                    @else
                        <x-ui.status-badge status="archived" size="sm" />
                    @endif
                </td>

                <!-- 6. ACTIONS -->
                <td class="px-6 py-4 text-right">
                    <div class="inline-flex items-center gap-1.5 justify-end">
                        <x-ui.button 
                            :href="route('admin.products.edit', $product)" 
                            variant="secondary" 
                            size="xs"
                            class="font-sans"
                        >
                            Edit
                        </x-ui.button>

                        @if($isTrashed)
                            <form method="POST" action="{{ route('admin.products.restore', $product) }}" class="inline">
                                @csrf
                                <x-ui.button 
                                    type="submit" 
                                    variant="secondary" 
                                    size="xs"
                                    class="font-sans text-emerald-400 border-emerald-500/30 hover:border-emerald-500/50 bg-emerald-500/10 hover:bg-emerald-500/20"
                                >
                                    Restore
                                </x-ui.button>
                            </form>
                        @else
                            <x-ui.button 
                                type="button" 
                                variant="ghost" 
                                size="xs"
                                class="font-sans text-zinc-400 hover:text-rose-400 hover:bg-rose-500/10"
                                @click="openArchiveModal('{{ route('admin.products.destroy', $product) }}', '{{ addslashes($product->name) }}')"
                            >
                                Archive
                            </x-ui.button>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach

        <!-- Pagination Footer inside DataTable -->
        @if($products->isNotEmpty())
            <x-slot:footer>
                <x-ui.pagination :paginator="$products" />
            </x-slot:footer>
        @endif
    </x-ui.data-table>

    <!-- ConfirmModal for Archiving Products -->
    <x-ui.confirm-modal
        name="archiveModalOpen"
        title="Archive Product"
        actionDynamic="archiveActionUrl"
        method="DELETE"
        confirmText="Archive Product"
        cancelText="Cancel"
        variant="danger"
    >
        <div class="p-3.5 bg-zinc-950/80 rounded-xl border border-white/[0.08] text-xs text-zinc-300 space-y-2 font-sans mb-2">
            <p class="text-zinc-200">
                Are you sure you want to archive <strong class="text-white" x-text="archiveProductName"></strong>?
            </p>
            <p class="text-[11px] leading-relaxed text-zinc-400">
                Archiving hides this product from the storefront. All historical orders, sales records, and variant inventory adjustments are preserved. You can restore it anytime from the Trashed filter.
            </p>
        </div>
    </x-ui.confirm-modal>
</div>
@endsection
