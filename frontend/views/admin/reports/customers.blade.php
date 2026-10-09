@extends('layouts.admin')

@section('title', 'Customer Acquisition & Loyalty Report')

@section('content')
<div class="space-y-6">
    @include('admin.reports._nav', [
        'currentRoute' => 'admin.reports.customers',
        'dateFrom' => $dateFrom,
        'dateTo' => $dateTo,
        'supportsCsv' => false,
    ])

    <!-- Top KPI Grid (3 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
        <!-- New Registered Accounts -->
        <x-ui.glass-card class="p-5 space-y-2">
            <div class="text-[11px] font-mono uppercase tracking-wider text-admin-muted font-semibold">
                New Registered Accounts
            </div>
            <div class="text-2xl font-bold font-mono text-white tabular-nums">
                {{ $report['new_customers'] }}
            </div>
            <div class="text-[11px] font-mono text-admin-muted">
                New customer accounts created in period
            </div>
        </x-ui.glass-card>

        <!-- Repeat Purchase Rate -->
        <x-ui.glass-card class="p-5 space-y-2">
            <div class="text-[11px] font-mono uppercase tracking-wider text-admin-muted font-semibold">
                Repeat Purchase Rate
            </div>
            <div class="text-2xl font-bold font-mono text-emerald-400 tabular-nums">
                {{ $report['repeat_rate'] }}%
            </div>
            <div class="text-[11px] font-mono text-admin-muted">
                {{ $report['repeat_customers'] }} of {{ $report['total_paid_customers'] }} clients with 2+ paid orders
            </div>
        </x-ui.glass-card>

        <!-- Active Purchasing Clients -->
        <x-ui.glass-card class="p-5 space-y-2">
            <div class="text-[11px] font-mono uppercase tracking-wider text-admin-muted font-semibold">
                Active Purchasing Clients
            </div>
            <div class="text-2xl font-bold font-mono text-white tabular-nums">
                {{ $report['total_paid_customers'] }}
            </div>
            <div class="text-[11px] font-mono text-admin-muted">
                Registered users with at least 1 paid order
            </div>
        </x-ui.glass-card>
    </div>

    <!-- 2-Column Insights Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Left Card — Client Retention Profile -->
        <x-ui.glass-card class="p-5 space-y-5">
            <div class="border-b border-white/[0.08] pb-4">
                <h3 class="text-xs font-mono uppercase tracking-widest text-white font-bold">
                    Client Retention Profile
                </h3>
                <p class="text-[11px] text-admin-muted mt-1">
                    Repeat order conversion and retention behavior among registered clientele
                </p>
            </div>

            @php
                $oneOrderPct = $report['total_paid_customers'] > 0 
                    ? round(($report['one_order_customers'] / $report['total_paid_customers']) * 100, 1) 
                    : 0;
            @endphp

            <div class="space-y-4">
                <!-- Single-Order Customers -->
                <div class="p-4 bg-zinc-950/40 rounded-xl border border-white/[0.05] space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium text-white text-xs">Single-Order Customers</div>
                            <div class="text-[10px] text-admin-muted font-mono mt-0.5">Clients with exactly 1 completed paid checkout</div>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-mono font-bold text-white tabular-nums">{{ $report['one_order_customers'] }}</span>
                            <span class="text-xs font-mono text-admin-muted ml-1">({{ $oneOrderPct }}%)</span>
                        </div>
                    </div>
                    <div class="h-2 rounded-full bg-zinc-900 border border-white/[0.05] overflow-hidden">
                        <div 
                            style="width: {{ $oneOrderPct }}%;" 
                            class="h-full bg-zinc-600 rounded-full transition-all duration-300"
                        ></div>
                    </div>
                </div>

                <!-- Repeat Loyal Customers -->
                <div class="p-4 bg-zinc-950/40 rounded-xl border border-white/[0.05] space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div>
                                <div class="font-medium text-emerald-400 text-xs">Repeat Loyal Customers (2+ Orders)</div>
                                <div class="text-[10px] text-admin-muted font-mono mt-0.5">Clients who completed multiple paid checkouts</div>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                Loyal Cohort
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-mono font-bold text-emerald-400 tabular-nums">{{ $report['repeat_customers'] }}</span>
                            <span class="text-xs font-mono text-emerald-500/80 ml-1">({{ $report['repeat_rate'] }}%)</span>
                        </div>
                    </div>
                    <div class="h-2 rounded-full bg-zinc-900 border border-white/[0.05] overflow-hidden">
                        <div 
                            style="width: {{ $report['repeat_rate'] }}%;" 
                            class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 rounded-full shadow-sm transition-all duration-300"
                        ></div>
                    </div>
                </div>
            </div>

            <div class="p-3 bg-zinc-950/60 border border-white/[0.05] rounded-xl text-[11px] text-zinc-400 font-sans leading-relaxed">
                Note: Repeat purchase rate is computed as <code class="text-zinc-200">Customers(2+ paid orders) / Total Active Purchasing Clients</code>. Guest buyers are evaluated separately in the Authentication Mix.
            </div>
        </x-ui.glass-card>

        <!-- Right Card — Order Authentication Mix (In Period) -->
        <x-ui.glass-card class="p-5 space-y-5">
            <div class="border-b border-white/[0.08] pb-4">
                <h3 class="text-xs font-mono uppercase tracking-widest text-white font-bold">
                    Order Authentication Mix (In Period)
                </h3>
                <p class="text-[11px] text-admin-muted mt-1">
                    Share of placed orders between guest shoppers and registered accounts
                </p>
            </div>

            <!-- Segmented Distribution Visualizer Bar -->
            <div class="space-y-2">
                <div class="h-3 rounded-full flex gap-1 overflow-hidden bg-zinc-900 p-0.5 border border-white/[0.08]">
                    @if($report['guest_share_percentage'] > 0)
                        <div 
                            style="width: {{ $report['guest_share_percentage'] }}%;" 
                            class="h-full rounded-full bg-zinc-500 transition-all duration-300"
                            title="Guest: {{ $report['guest_share_percentage'] }}%"
                        ></div>
                    @endif
                    @if($report['registered_share_percentage'] > 0)
                        <div 
                            style="width: {{ $report['registered_share_percentage'] }}%;" 
                            class="h-full rounded-full bg-gradient-to-r from-hoyos-red to-red-500 transition-all duration-300 shadow-sm"
                            title="Registered: {{ $report['registered_share_percentage'] }}%"
                        ></div>
                    @endif
                    @if($report['total_orders'] == 0)
                        <div class="w-full h-full rounded-full bg-zinc-800"></div>
                    @endif
                </div>
                <div class="flex items-center justify-between text-[10px] font-mono text-admin-muted px-0.5">
                    <span>Guest: {{ $report['guest_share_percentage'] }}%</span>
                    <span>Registered: {{ $report['registered_share_percentage'] }}%</span>
                </div>
            </div>

            <!-- Legend List -->
            <div class="space-y-3 pt-1">
                <!-- Registered Account Checkouts -->
                <div class="flex items-center justify-between p-3.5 bg-zinc-950/40 rounded-xl border border-white/[0.05]">
                    <div class="flex items-center gap-2.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-hoyos-red shrink-0 shadow-sm shadow-hoyos-red/50"></span>
                        <div>
                            <div class="text-xs font-medium text-white">Registered Account Checkouts</div>
                            <div class="text-[10px] font-mono text-admin-muted mt-0.5">Members signed in with customer account</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-mono text-zinc-300 tabular-nums font-semibold">{{ $report['registered_orders'] }} {{ Str::plural('order', $report['registered_orders']) }}</span>
                        <span class="px-2.5 py-1 rounded-full text-xs font-mono font-bold bg-hoyos-red/15 text-hoyos-red border border-hoyos-red/30 tabular-nums">
                            {{ $report['registered_share_percentage'] }}%
                        </span>
                    </div>
                </div>

                <!-- Guest Checkouts -->
                <div class="flex items-center justify-between p-3.5 bg-zinc-950/40 rounded-xl border border-white/[0.05]">
                    <div class="flex items-center gap-2.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-zinc-500 shrink-0"></span>
                        <div>
                            <div class="text-xs font-medium text-white">Guest Checkouts</div>
                            <div class="text-[10px] font-mono text-admin-muted mt-0.5">Direct checkout without member authentication</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-mono text-zinc-400 tabular-nums">{{ $report['guest_orders'] }} {{ Str::plural('order', $report['guest_orders']) }}</span>
                        <span class="px-2.5 py-1 rounded-full text-xs font-mono font-medium bg-zinc-900 border border-white/[0.08] text-zinc-300 tabular-nums">
                            {{ $report['guest_share_percentage'] }}%
                        </span>
                    </div>
                </div>

                <div class="pt-3 border-t border-white/[0.08] flex items-center justify-between text-xs font-mono font-medium text-zinc-400">
                    <span>Total Placed Orders in Range</span>
                    <span class="text-white font-bold tabular-nums">{{ $report['total_orders'] }}</span>
                </div>
            </div>
        </x-ui.glass-card>
    </div>
</div>
@endsection
