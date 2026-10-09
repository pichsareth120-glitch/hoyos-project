@extends('layouts.admin')

@section('title', 'Regional Deliveries Report')

@section('content')
<div class="space-y-6">
    @include('admin.reports._nav', [
        'currentRoute' => 'admin.reports.regions',
        'dateFrom' => $dateFrom,
        'dateTo' => $dateTo,
        'supportsCsv' => true,
    ])

    @php
        $regions = collect($report['regions']);
        $primaryTerritory = $regions->first();
        $totalZones = $regions->count();
        $totalPaidOrders = $regions->sum('paid_orders');
    @endphp

    <!-- Top KPI Cards (3 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
        <!-- Card 1: Primary Territory -->
        <x-ui.glass-card class="p-5 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-mono uppercase tracking-wider text-admin-muted font-semibold">Primary Territory</span>
                @if($primaryTerritory)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                        {{ $primaryTerritory['share_percentage'] }}% Volume Share
                    </span>
                @endif
            </div>
            <div class="text-2xl font-bold font-mono text-white truncate">
                {{ $primaryTerritory['province'] ?? 'None Recorded' }}
            </div>
            <div class="text-[11px] font-mono text-admin-muted">
                @if($primaryTerritory)
                    {{ $primaryTerritory['paid_orders'] }} paid orders &middot; ${{ $primaryTerritory['total_revenue'] }}
                @else
                    No regional deliveries recorded
                @endif
            </div>
        </x-ui.glass-card>

        <!-- Card 2: Total Delivered Revenue -->
        <x-ui.glass-card class="p-5 space-y-2">
            <div class="text-[11px] font-mono uppercase tracking-wider text-admin-muted font-semibold">Total Delivered Revenue</div>
            <div class="tabular-nums font-mono font-bold text-2xl text-white">
                ${{ $report['overall_revenue'] }}
            </div>
            <div class="text-[11px] font-mono text-admin-muted">
                Aggregated across {{ $totalZones }} {{ Str::plural('province', $totalZones) }}
            </div>
        </x-ui.glass-card>

        <!-- Card 3: Active Dispatch Zones -->
        <x-ui.glass-card class="p-5 space-y-2">
            <div class="text-[11px] font-mono uppercase tracking-wider text-admin-muted font-semibold">Active Dispatch Zones</div>
            <div class="tabular-nums font-mono font-bold text-2xl text-white">
                {{ $totalZones }}
            </div>
            <div class="text-[11px] font-mono text-admin-muted">
                {{ $totalPaidOrders }} total courier deliveries
            </div>
        </x-ui.glass-card>
    </div>

    <!-- Geographic Fulfillment & Revenue Card -->
    <x-ui.glass-card class="p-0 overflow-hidden">
        <div class="p-5 border-b border-white/[0.08] flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-xs font-mono uppercase tracking-widest text-white font-bold">
                        Geographic Revenue & Fulfillment Volume
                    </h3>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold bg-zinc-900 border border-white/[0.08] text-zinc-400">
                        {{ $totalZones }} {{ Str::plural('Territory', $totalZones) }}
                    </span>
                </div>
                <p class="text-[11px] text-admin-muted mt-1">
                    Breakdown across Cambodian provinces for paid checkouts
                </p>
            </div>
            <div class="text-right">
                <span class="text-[10px] font-mono uppercase tracking-wider text-admin-muted">Total Gross:</span>
                <span class="ml-1 text-xs font-mono text-white font-bold tabular-nums">${{ $report['overall_revenue'] }}</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-white/[0.08] bg-zinc-950/60 text-[10px] font-mono uppercase tracking-wider text-zinc-400 font-bold">
                        <th class="px-5 py-3">PROVINCE / TERRITORY</th>
                        <th class="px-5 py-3 text-center">PAID ORDERS</th>
                        <th class="px-5 py-3 text-right">REVENUE</th>
                        <th class="px-5 py-3 w-1/3">VOLUME SHARE</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.04] font-mono">
                    @forelse($regions as $reg)
                        <tr class="hover:bg-white/[0.02] transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 rounded-lg bg-zinc-900 border border-white/[0.08] flex items-center justify-center text-hoyos-red shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </span>
                                    <span class="text-zinc-100 text-xs font-medium">{{ $reg['province'] }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="px-2.5 py-1 rounded-full text-xs font-mono font-semibold bg-zinc-900 border border-white/[0.08] text-zinc-300">
                                    {{ $reg['paid_orders'] }} {{ Str::plural('order', $reg['paid_orders']) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right font-bold text-white tabular-nums">
                                ${{ $reg['total_revenue'] }}
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 h-2 rounded-full bg-zinc-900 overflow-hidden border border-white/[0.05]">
                                        <div 
                                            style="width: {{ $reg['share_percentage'] }}%;" 
                                            class="h-full bg-gradient-to-r from-hoyos-red to-red-500 rounded-full shadow-sm transition-all duration-300"
                                        ></div>
                                    </div>
                                    <span class="text-xs font-mono text-zinc-300 font-semibold w-12 text-right tabular-nums">
                                        {{ $reg['share_percentage'] }}%
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-admin-muted font-mono">
                                No regional delivery data found in this timeframe.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.glass-card>
</div>
@endsection
