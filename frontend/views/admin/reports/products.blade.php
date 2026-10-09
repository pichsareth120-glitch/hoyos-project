@extends('layouts.admin')

@section('title', 'Top Products & Sizing Report')

@section('content')
<div class="space-y-6">
    @include('admin.reports._nav', [
        'currentRoute' => 'admin.reports.products',
        'dateFrom' => $dateFrom,
        'dateTo' => $dateTo,
        'supportsCsv' => true,
    ])

    @php
        $topProducts = collect($report['top_products']);
        $totalVolume = $topProducts->sum('units_sold');
        $maxProductUnits = $topProducts->max('units_sold') ?: 1;

        // Query catalog metadata safely (handles soft deleted items too)
        $productNames = $topProducts->pluck('product_name')->filter()->unique();
        $matchedProducts = \App\Models\Product::withTrashed()
            ->with(['primaryImage', 'images'])
            ->whereIn('name', $productNames)
            ->get()
            ->keyBy('name');

        // Size velocity data
        $sizeData = collect($report['size_breakdown'])->keyBy(fn($i) => strtoupper(trim($i['size'])));
        $standardSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $extraSizes = $sizeData->keys()->diff($standardSizes)->all();
        $displaySizes = array_merge($standardSizes, $extraSizes);
        $maxSizeUnits = collect($report['size_breakdown'])->max('units_sold') ?: 1;
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left (7 cols) — Top Apparel Items by Volume -->
        <div class="lg:col-span-7">
            <x-ui.glass-card class="p-0 overflow-hidden">
                <div class="p-5 border-b border-white/[0.08] flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-xs font-mono uppercase tracking-widest text-white font-bold">
                                Top 20 Apparel Items By Volume
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold bg-zinc-900 border border-white/[0.08] text-zinc-400">
                                {{ $topProducts->count() }} {{ Str::plural('item', $topProducts->count()) }}
                            </span>
                        </div>
                        <p class="text-[11px] text-admin-muted mt-1">
                            Grouped by immutable product name snapshot; survives deleted or renamed catalog items
                        </p>
                    </div>
                    <span class="text-[10px] font-mono text-admin-muted">
                        Total Units: <strong class="text-white">{{ $totalVolume }}</strong>
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-white/[0.08] bg-zinc-950/60 text-[10px] font-mono uppercase tracking-wider text-zinc-400 font-bold">
                                <th class="px-4 py-3 text-center w-12">RANK</th>
                                <th class="px-4 py-3">PRODUCT</th>
                                <th class="px-4 py-3 text-center">UNITS SOLD</th>
                                <th class="px-4 py-3 text-right">AVG PRICE</th>
                                <th class="px-4 py-3 text-right">REVENUE</th>
                                <th class="px-4 py-3 text-center w-28">SHARE</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.04] font-mono">
                            @forelse($topProducts as $idx => $prod)
                                @php
                                    $rank = $idx + 1;
                                    $units = (int)$prod['units_sold'];
                                    $sharePct = $totalVolume > 0 ? round(($units / $totalVolume) * 100, 1) : 0;
                                    $catalogItem = $matchedProducts->get($prod['product_name']);
                                    $collectionName = $catalogItem?->collection_name ?? 'Streetwear';
                                    $imgUrl = $catalogItem?->primaryImage?->image_path 
                                        ?? $catalogItem?->images?->first()?->image_path;
                                @endphp
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <!-- RANK -->
                                    <td class="px-4 py-3.5 text-center">
                                        @if($rank === 1)
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-mono font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30">
                                                #1
                                            </span>
                                        @elseif($rank <= 3)
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-mono font-semibold bg-hoyos-red/15 text-hoyos-red border border-hoyos-red/30">
                                                #{{ $rank }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-mono text-admin-muted bg-zinc-900 border border-white/[0.06]">
                                                #{{ $rank }}
                                            </span>
                                        @endif
                                    </td>

                                    <!-- PRODUCT -->
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center gap-3">
                                            @if($imgUrl)
                                                <img 
                                                    src="{{ asset('storage/' . $imgUrl) }}" 
                                                    alt="{{ $prod['product_name'] }}" 
                                                    class="w-9 h-9 rounded-lg border border-white/[0.08] object-cover bg-zinc-900 shrink-0"
                                                    onerror="this.onerror=null; this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');"
                                                />
                                                <div class="hidden w-9 h-9 rounded-lg border border-white/[0.08] bg-zinc-900 shrink-0 flex items-center justify-center text-zinc-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                                    </svg>
                                                </div>
                                            @else
                                                <div class="w-9 h-9 rounded-lg border border-white/[0.08] bg-zinc-900 shrink-0 flex items-center justify-center text-zinc-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                                    </svg>
                                                </div>
                                            @endif
                                            <div class="min-w-0 flex-1">
                                                <div class="text-white font-medium text-xs truncate max-w-[200px]" title="{{ $prod['product_name'] }}">
                                                    {{ $prod['product_name'] }}
                                                </div>
                                                <div class="text-[10px] font-mono text-zinc-400 uppercase tracking-wider mt-0.5">
                                                    {{ $collectionName }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- UNITS SOLD -->
                                    <td class="px-4 py-3.5 text-center tabular-nums font-mono text-zinc-200 font-bold">
                                        {{ $units }}
                                    </td>

                                    <!-- AVG PRICE -->
                                    <td class="px-4 py-3.5 text-right tabular-nums font-mono text-zinc-400">
                                        ${{ $prod['avg_price'] }}
                                    </td>

                                    <!-- REVENUE -->
                                    <td class="px-4 py-3.5 text-right tabular-nums font-mono font-bold text-white">
                                        ${{ $prod['total_revenue'] }}
                                    </td>

                                    <!-- SHARE -->
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center justify-center gap-2">
                                            <div class="bg-zinc-900 h-1.5 w-16 rounded-full overflow-hidden border border-white/[0.05]">
                                                <div 
                                                    style="width: {{ $sharePct }}%;" 
                                                    class="h-full bg-gradient-to-r from-hoyos-red to-red-500 rounded-full"
                                                ></div>
                                            </div>
                                            <span class="text-[10px] font-mono text-zinc-400 w-8 text-right">{{ $sharePct }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-admin-muted font-mono">
                                        No product sales recorded in this period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.glass-card>
        </div>

        <!-- Right (5 cols) — Size Velocity Distribution Chart -->
        <div class="lg:col-span-5">
            <x-ui.glass-card class="p-5">
                <div class="border-b border-white/[0.08] pb-4 mb-5">
                    <h3 class="text-xs font-mono uppercase tracking-widest text-white font-bold">
                        Size Velocity Breakdown
                    </h3>
                    <p class="text-[11px] text-admin-muted mt-1">
                        Units sold and gross revenue per apparel sizing
                    </p>
                </div>

                <div class="space-y-4">
                    @foreach($displaySizes as $sz)
                        @php
                            $sizeItem = $sizeData->get($sz);
                            $units = $sizeItem['units_sold'] ?? 0;
                            $rev = $sizeItem['total_revenue'] ?? '0.00';
                            $pct = $units > 0 ? round(($units / $maxSizeUnits) * 100, 1) : 0;
                        @endphp
                        <div class="p-3 bg-zinc-950/40 rounded-xl border border-white/[0.05] space-y-2">
                            <!-- Top Row: Size pill, units sold, total revenue -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="px-2.5 py-0.5 rounded-md text-xs font-mono font-bold text-white bg-zinc-900 border border-white/[0.1] shadow-xs">
                                        {{ $sz }}
                                    </span>
                                    <span class="text-xs font-mono text-zinc-300">
                                        <strong class="text-white">{{ $units }}</strong> {{ Str::plural('unit', $units) }}
                                    </span>
                                </div>
                                <span class="text-xs font-mono font-bold text-white tabular-nums">
                                    ${{ $rev }}
                                </span>
                            </div>

                            <!-- Bottom Bar: Sleek horizontal progress bar -->
                            <div class="h-2 rounded-full bg-zinc-900 border border-white/[0.05] overflow-hidden">
                                <div 
                                    style="width: {{ $pct }}%;" 
                                    class="h-full rounded-full bg-gradient-to-r from-hoyos-red to-red-500 shadow-sm transition-all duration-300"
                                ></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.glass-card>
        </div>
    </div>
</div>
@endsection
