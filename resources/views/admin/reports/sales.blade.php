@extends('layouts.admin')

@section('title', 'Sales & Revenue Report')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">
    {{-- SHARED REPORTS HEADER, TABS & FILTER TOOLBAR --}}
    @include('admin.reports._nav', [
        'currentRoute' => 'admin.reports.sales',
        'dateFrom'     => $dateFrom,
        'dateTo'       => $dateTo,
        'supportsCsv'  => true,
    ])

    {{-- SUMMARY METRIC KPI CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Period Revenue -->
        <div class="glass-panel-elevated rounded-2xl p-5 relative overflow-hidden group">
            <!-- Subtle Top Hairline Accent -->
            <div class="absolute inset-x-0 top-0 h-[1.5px] bg-gradient-to-r from-transparent via-[#FF1744]/70 to-transparent pointer-events-none"></div>

            <div class="flex items-start justify-between gap-3 mb-3">
                <span class="text-[11px] font-mono uppercase tracking-wider text-zinc-400 font-semibold">
                    Total Period Revenue
                </span>
                <div class="w-8 h-8 rounded-xl bg-[#FF1744]/10 border border-[#FF1744]/25 flex items-center justify-center text-[#FF1744] shadow-[0_0_12px_rgba(255,23,68,0.2)] group-hover:scale-105 transition-transform shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <div class="space-y-1">
                <div class="tabular-nums font-mono font-bold text-2xl sm:text-3xl text-white tracking-tight">
                    ${{ $report['total_revenue'] }}
                </div>
                <div class="flex items-center justify-between text-xs text-zinc-400 pt-0.5">
                    <span>Paid, non-cancelled orders</span>
                    <span class="font-mono text-[11px] text-zinc-300 font-medium">{{ $report['total_paid_orders'] }} paid</span>
                </div>
            </div>
        </div>

        <!-- 2. Completed Paid Orders -->
        <div class="glass-panel-elevated rounded-2xl p-5 relative overflow-hidden group">
            <!-- Subtle Top Hairline Accent -->
            <div class="absolute inset-x-0 top-0 h-[1.5px] bg-gradient-to-r from-transparent via-emerald-400/60 to-transparent pointer-events-none"></div>

            <div class="flex items-start justify-between gap-3 mb-3">
                <span class="text-[11px] font-mono uppercase tracking-wider text-zinc-400 font-semibold">
                    Completed Paid Orders
                </span>
                <div class="w-8 h-8 rounded-xl bg-emerald-500/10 border border-emerald-500/25 flex items-center justify-center text-emerald-400 shadow-[0_0_12px_rgba(52,211,153,0.15)] group-hover:scale-105 transition-transform shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <div class="space-y-1">
                <div class="tabular-nums font-mono font-bold text-2xl sm:text-3xl text-white tracking-tight">
                    {{ $report['total_paid_orders'] }}
                </div>
                <div class="flex items-center justify-between text-xs text-zinc-400 pt-0.5">
                    <span>Full payment verified</span>
                    <span class="font-mono text-[11px] text-emerald-400 font-medium">Captured</span>
                </div>
            </div>
        </div>

        <!-- 3. Period AOV -->
        <div class="glass-panel-elevated rounded-2xl p-5 relative overflow-hidden group">
            <!-- Subtle Top Hairline Accent -->
            <div class="absolute inset-x-0 top-0 h-[1.5px] bg-gradient-to-r from-transparent via-sky-400/60 to-transparent pointer-events-none"></div>

            <div class="flex items-start justify-between gap-3 mb-3">
                <span class="text-[11px] font-mono uppercase tracking-wider text-zinc-400 font-semibold">
                    Period AOV
                </span>
                <div class="w-8 h-8 rounded-xl bg-sky-500/10 border border-sky-500/25 flex items-center justify-center text-sky-400 shadow-[0_0_12px_rgba(56,189,248,0.15)] group-hover:scale-105 transition-transform shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>

            <div class="space-y-1">
                <div class="tabular-nums font-mono font-bold text-2xl sm:text-3xl text-white tracking-tight">
                    ${{ $report['overall_aov'] }}
                </div>
                <div class="flex items-center justify-between text-xs text-zinc-400 pt-0.5">
                    <span>Average order value</span>
                    <span class="font-mono text-[11px] text-zinc-300 font-medium">Per checkout</span>
                </div>
            </div>
        </div>

        <!-- 4. Pending / In-Flight -->
        <div class="glass-panel-elevated rounded-2xl p-5 relative overflow-hidden group">
            <!-- Subtle Top Hairline Accent -->
            <div class="absolute inset-x-0 top-0 h-[1.5px] bg-gradient-to-r from-transparent via-amber-400/60 to-transparent pointer-events-none"></div>

            <div class="flex items-start justify-between gap-3 mb-3">
                <span class="text-[11px] font-mono uppercase tracking-wider text-zinc-400 font-semibold">
                    Pending / In-Flight
                </span>
                <div class="w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/25 flex items-center justify-center text-amber-400 shadow-[0_0_12px_rgba(251,191,36,0.15)] group-hover:scale-105 transition-transform shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <div class="space-y-1">
                <div class="tabular-nums font-mono font-bold text-2xl sm:text-3xl text-amber-400 tracking-tight">
                    ${{ $report['pending_revenue'] }}
                </div>
                <div class="flex items-center justify-between text-xs text-zinc-400 pt-0.5">
                    <span>Awaiting COD or KHQR</span>
                    <span class="font-mono text-[11px] text-amber-400/90 font-medium">Unsettled</span>
                </div>
            </div>
        </div>
    </div>

    {{-- INTERACTIVE SVG REVENUE BY DAY CHART --}}
    @php
        $buckets      = collect($report['buckets']);
        $maxRev       = (float)($buckets->max('revenue_float') ?: ($buckets->max('revenue') ?: 1));
        if ($maxRev <= 0) {
            $maxRev = 100.0;
        }
        $chartBuckets = $buckets->slice(-30)->values();
        $barCount     = $chartBuckets->count();
    @endphp

    <div class="glass-panel-elevated rounded-2xl shadow-sm overflow-hidden"
         x-data="{
             tooltip: false,
             tip: { date: '', fullDate: '', orders: 0, revenue: '0.00', aov: '0.00', x: 0, y: 0 },
             show(ev, dateFormatted, dateFull, orders, revenue, aov) {
                 const svg = ev.target.closest('svg');
                 const rect = svg.getBoundingClientRect();
                 const relX = ev.clientX - rect.left;
                 const relY = ev.clientY - rect.top;
                 this.tip = {
                     date: dateFormatted,
                     fullDate: dateFull,
                     orders: orders,
                     revenue: revenue,
                     aov: aov,
                     x: Math.min(Math.max(relX, 90), rect.width - 90),
                     y: Math.max(relY - 10, 10)
                 };
                 this.tooltip = true;
             },
             hide() { this.tooltip = false; }
         }">

        <!-- Chart Card Header -->
        <div class="p-5 sm:p-6 border-b border-white/[0.08] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-1.5 h-3.5 rounded-full bg-[#FF1744]"></span>
                    <h2 class="text-xs font-mono uppercase tracking-widest text-white font-bold">
                        Revenue By {{ ucfirst($report['interval']) }}
                    </h2>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-semibold bg-white/[0.04] border border-white/[0.08] text-zinc-300">
                        {{ $barCount }} {{ \Illuminate\Support\Str::plural('time bucket', $barCount) }}
                    </span>
                </div>
                <p class="text-[11px] text-zinc-400 mt-1 pl-3.5">
                    {{ $report['interval'] === 'month' ? 'Aggregated by calendar month' : 'Aggregated per local calendar date' }} &middot; Local store timezone: {{ config('hoyos.report_timezone', 'Asia/Phnom_Penh') }}
                </p>
            </div>

            <!-- Peak Revenue Badge -->
            <div class="flex items-center gap-2 self-start sm:self-auto">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/[0.03] border border-white/[0.08] text-[11px] font-mono text-zinc-300">
                    <span class="w-2 h-2 rounded-full bg-[#FF1744] shadow-[0_0_8px_#FF1744]"></span>
                    <span class="text-zinc-400">Peak:</span>
                    <strong class="text-white font-bold">${{ number_format($maxRev, 2) }}</strong>
                </div>
            </div>
        </div>

        @if($barCount > 0)
            <div class="relative p-5 sm:p-6">
                <!-- SVG Visualization -->
                <svg viewBox="0 0 840 250" class="w-full h-56 sm:h-64 overflow-visible"
                     @mouseleave="hide()">
                    <defs>
                        <!-- Sleek Bar Gradient -->
                        <linearGradient id="revenueBarGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#FF1744" stop-opacity="0.95"/>
                            <stop offset="60%" stop-color="#FF1744" stop-opacity="0.65"/>
                            <stop offset="100%" stop-color="#FF1744" stop-opacity="0.25"/>
                        </linearGradient>

                        <!-- Subtle Hover Bar Gradient -->
                        <linearGradient id="revenueBarHoverGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#FF365B" stop-opacity="1"/>
                            <stop offset="100%" stop-color="#FF1744" stop-opacity="0.45"/>
                        </linearGradient>

                        <filter id="barGlow" x="-20%" y="-20%" width="140%" height="140%">
                            <feDropShadow dx="0" dy="0" stdDeviation="3" flood-color="#FF1744" flood-opacity="0.4"/>
                        </filter>
                    </defs>

                    <!-- Horizontal Grid Lines and Y-Axis Ticks -->
                    @foreach([0.25, 0.50, 0.75, 1.0] as $gridFraction)
                        @php $gy = 200 - ($gridFraction * 170) @endphp
                        <line x1="60" y1="{{ $gy }}" x2="825" y2="{{ $gy }}"
                              stroke="rgba(255,255,255,{{ $gridFraction === 1.0 ? '0.08' : '0.04' }})"
                              stroke-width="1"
                              stroke-dasharray="{{ $gridFraction === 1.0 ? '0' : '4 4' }}"/>
                        <text x="52" y="{{ $gy + 3.5 }}" fill="rgba(255,255,255,0.4)"
                              font-size="9" text-anchor="end" font-family="'Space Mono', monospace">
                            ${{ number_format($maxRev * $gridFraction, 0) }}
                        </text>
                    @endforeach

                    <!-- Baseline Zero Grid Line & Tick -->
                    <line x1="60" y1="200" x2="825" y2="200" stroke="rgba(255,255,255,0.12)" stroke-width="1"/>
                    <text x="52" y="203.5" fill="rgba(255,255,255,0.25)"
                          font-size="9" text-anchor="end" font-family="'Space Mono', monospace">
                        $0
                    </text>

                    <!-- Proportional Bar Layout Calculations -->
                    @php
                        $plotWidth = 765;
                        $originX   = 60;
                        $slotWidth = $plotWidth / max($barCount, 1);

                        // Balanced bar sizing formula:
                        // For <= 7 bars, use 40-48px width centered in slot.
                        // For > 7 bars, dynamically scale width between 10px and 36px.
                        if ($barCount <= 7) {
                            $barW = min(44, max(28, (int)($slotWidth * 0.55)));
                        } else {
                            $barW = max(10, min(36, (int)($slotWidth * 0.65)));
                        }
                    @endphp

                    @foreach($chartBuckets as $i => $bucket)
                        @php
                            $bRevFloat = (float)($bucket['revenue_float'] ?? $bucket['revenue']);
                            $bHeight   = max(3, ($maxRev > 0 ? $bRevFloat / $maxRev : 0) * 170);
                            $bX        = $originX + ($i * $slotWidth) + (($slotWidth - $barW) / 2);
                            $bY        = 200 - $bHeight;
                            $hasRev    = $bRevFloat > 0;

                            // Parse and format date label properly
                            try {
                                $cDate = \Carbon\Carbon::parse($bucket['period']);
                                $shortDate = $report['interval'] === 'month' ? $cDate->format('M Y') : $cDate->format('M d');
                                $fullDate = $cDate->format('D, M j, Y');
                            } catch (\Throwable $e) {
                                $shortDate = $bucket['period'];
                                $fullDate = $bucket['period'];
                            }

                            // Smart label rendering decimation for many bars
                            $showLabel = false;
                            if ($barCount <= 12) {
                                $showLabel = true;
                            } elseif ($barCount <= 20) {
                                $showLabel = ($i % 2 === 0) || ($i === $barCount - 1);
                            } else {
                                $showLabel = ($i % 4 === 0) || ($i === $barCount - 1);
                            }
                        @endphp

                        <!-- Bar Group for Hover Interactions -->
                        <g class="cursor-pointer group"
                           @mouseenter="show($event, '{{ addslashes($shortDate) }}', '{{ addslashes($fullDate) }}', {{ $bucket['paid_orders'] }}, '{{ $bucket['revenue'] }}', '{{ $bucket['aov'] }}')">

                            <!-- Invisible Wider Touch/Hover Hitbox Area -->
                            <rect x="{{ round($bX - 4, 2) }}" y="30"
                                  width="{{ $barW + 8 }}" height="170"
                                  fill="transparent"
                                  class="transition-colors hover:fill-white/[0.02]"/>

                            <!-- Main Revenue Bar -->
                            <rect x="{{ round($bX, 2) }}" y="{{ round($bY, 2) }}"
                                  width="{{ $barW }}" height="{{ round($bHeight, 2) }}"
                                  rx="4" ry="4"
                                  fill="{{ $hasRev ? 'url(#revenueBarGrad)' : 'rgba(255,255,255,0.04)' }}"
                                  stroke="{{ $hasRev ? 'rgba(255,23,68,0.4)' : 'rgba(255,255,255,0.06)' }}"
                                  stroke-width="1"
                                  class="transition-all duration-150 group-hover:fill-[url(#revenueBarHoverGrad)] group-hover:stroke-[#FF365B]"
                            />

                            <!-- Top Glowing Cap Highlight for Days with Revenue -->
                            @if($hasRev)
                                <line x1="{{ round($bX + 2, 2) }}" y1="{{ round($bY, 2) }}"
                                      x2="{{ round($bX + $barW - 2, 2) }}" y2="{{ round($bY, 2) }}"
                                      stroke="#FF1744" stroke-width="2.5" stroke-linecap="round"
                                      filter="url(#barGlow)"/>
                                <circle cx="{{ round($bX + $barW / 2, 2) }}" cy="{{ round($bY, 2) }}"
                                        r="2" fill="#FFFFFF"/>
                            @endif

                            <!-- Date Axis Label (Cleanly Formatted, Never Truncated) -->
                            @if($showLabel)
                                <text x="{{ round($bX + $barW / 2, 2) }}" y="222"
                                      fill="rgba(255,255,255,0.45)" font-size="9"
                                      text-anchor="middle" font-family="'Space Mono', monospace"
                                      class="transition-colors group-hover:fill-white">
                                    {{ $shortDate }}
                                </text>
                            @endif
                        </g>
                    @endforeach
                </svg>

                <!-- Floating Dark Glass Tooltip -->
                <div x-show="tooltip" x-cloak
                     :style="`left:${tip.x}px; top:${tip.y}px`"
                     class="absolute pointer-events-none z-30 -translate-x-1/2 -translate-y-full mb-3 bg-[#0D0D12]/95 backdrop-blur-xl border border-white/[0.14] rounded-xl px-3.5 py-2.5 text-xs font-mono shadow-[0_12px_32px_rgba(0,0,0,0.85)] min-w-[170px] space-y-2 transition-transform duration-75">
                    <!-- Date Header -->
                    <div class="border-b border-white/[0.08] pb-1.5 flex items-center justify-between">
                        <span class="text-white font-bold" x-text="tip.date"></span>
                        <span class="text-[10px] text-zinc-400" x-text="tip.fullDate"></span>
                    </div>

                    <!-- Revenue Amount -->
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-400">Revenue:</span>
                        <span class="text-white font-bold">$<span x-text="tip.revenue"></span></span>
                    </div>

                    <!-- Paid Orders -->
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-zinc-400">Paid Orders:</span>
                        <span class="text-emerald-400 font-semibold" x-text="tip.orders"></span>
                    </div>

                    <!-- AOV -->
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-zinc-400">Bucket AOV:</span>
                        <span class="text-zinc-300 font-semibold">$<span x-text="tip.aov"></span></span>
                    </div>
                </div>
            </div>
        @else
            <!-- Empty State for Empty Date Range -->
            <div class="px-6 py-16 text-center space-y-3 font-mono">
                <div class="w-12 h-12 rounded-2xl bg-white/[0.03] border border-white/[0.08] flex items-center justify-center text-zinc-500 mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div class="text-xs text-zinc-400 uppercase tracking-wider font-semibold">No Sales Transactions In Range</div>
                <p class="text-[11px] text-zinc-500 max-w-sm mx-auto font-sans">
                    No completed payments match the selected date window. Try expanding your filter range to view historical analytics.
                </p>
            </div>
        @endif
    </div>

    {{-- PAYMENT SPLIT & ORDER LIFECYCLE --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 1. Payment Method Breakdown -->
        <div class="glass-panel-elevated rounded-2xl p-5 sm:p-6 space-y-5">
            <div class="border-b border-white/[0.08] pb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-mono uppercase tracking-widest text-white font-bold flex items-center gap-2">
                        <span class="w-1.5 h-3 rounded-full bg-[#FF1744]"></span>
                        <span>Payment Method Breakdown</span>
                    </h3>
                    <p class="text-[11px] text-zinc-400 mt-1 pl-3.5">
                        Settlement channel distribution across non-cancelled orders
                    </p>
                </div>
                <span class="text-[10px] font-mono text-zinc-400 uppercase tracking-wider px-2 py-1 rounded bg-white/[0.03] border border-white/[0.06]">
                    Total Rev: ${{ $report['total_revenue'] }}
                </span>
            </div>

            <div class="space-y-3.5">
                @forelse($report['payment_split'] as $split)
                    @php
                        $isKhqr = ($split['method'] === 'aba_khqr');
                        $settleRate = $split['total_orders'] > 0 ? round(($split['paid_orders'] / $split['total_orders']) * 100) : 0;
                    @endphp
                    <div class="p-4 rounded-xl bg-white/[0.02] border border-white/[0.06] hover:border-white/[0.12] transition-all space-y-2.5">
                        <div class="flex items-center justify-between font-mono text-xs">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-md {{ $isKhqr ? 'bg-[#FF1744]/15 text-[#FF1744] border border-[#FF1744]/30' : 'bg-white/[0.05] text-zinc-300 border border-white/[0.1]' }} flex items-center justify-center shrink-0">
                                    @if($isKhqr)
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                        </svg>
                                    @else
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    @endif
                                </div>
                                <span class="font-bold text-white tracking-wide">{{ $split['label'] }}</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="font-bold text-white">${{ $split['revenue'] }}</span>
                                <span class="px-2 py-0.5 rounded-full {{ $isKhqr ? 'bg-[#FF1744]/15 text-[#FF365B] border border-[#FF1744]/30' : 'bg-white/[0.05] text-zinc-400 border border-white/[0.08]' }} text-[10px] font-mono font-semibold">
                                    {{ $split['share'] }}%
                                </span>
                            </div>
                        </div>

                        <!-- Progress Bar Track -->
                        <div class="w-full h-2 rounded-full bg-white/[0.05] overflow-hidden p-[1px]">
                            <div style="width: {{ $split['share'] }}%"
                                 class="h-full rounded-full transition-all duration-300 {{ $isKhqr ? 'bg-gradient-to-r from-[#FF1744] to-[#FF365B] shadow-[0_0_8px_rgba(255,23,68,0.4)]' : 'bg-zinc-500' }}"></div>
                        </div>

                        <div class="flex items-center justify-between text-[11px] font-mono text-zinc-400 pt-0.5">
                            <span class="flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full {{ $split['paid_orders'] > 0 ? 'bg-emerald-400' : 'bg-zinc-600' }}"></span>
                                <strong class="text-zinc-200">{{ $split['paid_orders'] }}</strong> paid orders
                            </span>
                            <span>{{ $split['total_orders'] }} total active ({{ $settleRate }}% rate)</span>
                        </div>
                    </div>
                @empty
                    <div class="text-xs text-zinc-400 py-6 text-center font-mono">
                        No payment data recorded in this timeframe.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- 2. Order Lifecycle Pipeline -->
        <div class="glass-panel-elevated rounded-2xl p-5 sm:p-6 space-y-5">
            <div class="border-b border-white/[0.08] pb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-mono uppercase tracking-widest text-white font-bold flex items-center gap-2">
                        <span class="w-1.5 h-3 rounded-full bg-sky-400"></span>
                        <span>Order Lifecycle Pipeline</span>
                    </h3>
                    <p class="text-[11px] text-zinc-400 mt-1 pl-3.5">
                        Current operational status of all checkouts placed in range
                    </p>
                </div>
                @php
                    $totalOrders = array_sum(array_values($report['status_counts']));
                @endphp
                <span class="text-[10px] font-mono text-zinc-400 uppercase tracking-wider px-2 py-1 rounded bg-white/[0.03] border border-white/[0.06]">
                    {{ $totalOrders }} Total {{ \Illuminate\Support\Str::plural('Order', $totalOrders) }}
                </span>
            </div>

            @php
                $stageConfig = [
                    'confirmed'  => [
                        'label' => 'Confirmed',
                        'color' => 'text-sky-400',
                        'bg'    => 'bg-sky-500/10',
                        'border'=> 'border-sky-500/20',
                        'bar'   => 'bg-sky-400',
                    ],
                    'processing' => [
                        'label' => 'Processing',
                        'color' => 'text-amber-400',
                        'bg'    => 'bg-amber-500/10',
                        'border'=> 'border-amber-500/20',
                        'bar'   => 'bg-amber-400',
                    ],
                    'shipped'    => [
                        'label' => 'Shipped',
                        'color' => 'text-purple-400',
                        'bg'    => 'bg-purple-500/10',
                        'border'=> 'border-purple-500/20',
                        'bar'   => 'bg-purple-400',
                    ],
                    'delivered'  => [
                        'label' => 'Delivered',
                        'color' => 'text-emerald-400',
                        'bg'    => 'bg-emerald-500/10',
                        'border'=> 'border-emerald-500/20',
                        'bar'   => 'bg-emerald-400',
                    ],
                    'cancelled'  => [
                        'label' => 'Cancelled',
                        'color' => 'text-rose-400',
                        'bg'    => 'bg-rose-500/10',
                        'border'=> 'border-rose-500/20',
                        'bar'   => 'bg-rose-500',
                    ],
                ];
            @endphp

            <!-- 5-Card Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 sm:gap-3">
                @foreach($stageConfig as $st => $cfg)
                    @php
                        $count = $report['status_counts'][$st] ?? 0;
                        $pctOfTotal = $totalOrders > 0 ? round(($count / $totalOrders) * 100) : 0;
                    @endphp
                    <div class="p-3.5 {{ $cfg['bg'] }} border {{ $cfg['border'] }} rounded-xl space-y-1.5 transition-all hover:scale-[1.02]">
                        <div class="flex items-center justify-between text-[10px] font-mono uppercase tracking-wider {{ $cfg['color'] }} font-semibold">
                            <span>{{ $cfg['label'] }}</span>
                            <span class="opacity-75">{{ $pctOfTotal }}%</span>
                        </div>
                        <div class="tabular-nums font-mono font-bold text-2xl {{ $cfg['color'] }}">
                            {{ $count }}
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Segmented Fulfillment Progress Bar -->
            @if($totalOrders > 0)
                <div class="space-y-2 pt-1 border-t border-white/[0.06]">
                    <div class="flex items-center justify-between text-[10px] font-mono text-zinc-400 uppercase tracking-wider">
                        <span>Fulfillment Progress</span>
                        <span>Pipeline Distribution</span>
                    </div>

                    <div class="flex rounded-full overflow-hidden h-2.5 bg-white/[0.04] p-[1px]">
                        @foreach($stageConfig as $st => $cfg)
                            @php
                                $cnt = $report['status_counts'][$st] ?? 0;
                                $pct = round(($cnt / $totalOrders) * 100);
                            @endphp
                            @if($pct > 0)
                                <div class="{{ $cfg['bar'] }} transition-all duration-300"
                                     style="width:{{ $pct }}%"
                                     title="{{ $cfg['label'] }}: {{ $cnt }} ({{ $pct }}%)"></div>
                            @endif
                        @endforeach
                    </div>

                    <!-- Progress Legend -->
                    <div class="flex flex-wrap items-center gap-x-3.5 gap-y-1 text-[10px] font-mono text-zinc-400 pt-1">
                        @foreach($stageConfig as $st => $cfg)
                            @php $cnt = $report['status_counts'][$st] ?? 0; @endphp
                            @if($cnt > 0)
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full {{ $cfg['bar'] }}"></span>
                                    <span>{{ $cfg['label'] }}: <strong class="text-zinc-200">{{ $cnt }}</strong></span>
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- BUCKET DETAIL TABLE (Collapsible) --}}
    <div class="glass-panel-elevated rounded-2xl overflow-hidden border border-white/[0.08] shadow-sm"
         x-data="{ open: false }">
        <button type="button"
                @click="open = !open"
                class="w-full px-5 py-4 flex items-center justify-between hover:bg-white/[0.02] transition-colors focus-ring cursor-pointer">
            <div class="flex items-center gap-2.5 text-left">
                <span class="text-xs font-mono uppercase tracking-widest text-white font-bold">
                    Revenue By {{ ucfirst($report['interval']) }} — Detail Table
                </span>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-mono text-zinc-400 bg-white/[0.04] border border-white/[0.08]">
                    {{ count($report['buckets']) }} {{ \Illuminate\Support\Str::plural('bucket', count($report['buckets'])) }}
                </span>
            </div>
            <div class="flex items-center gap-2 text-zinc-400 text-xs font-mono">
                <span x-text="open ? 'Collapse Table' : 'Expand Table'"></span>
                <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-zinc-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </button>

        <div x-show="open" x-cloak class="overflow-x-auto border-t border-white/[0.08]">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-white/[0.02] border-b border-white/[0.08] text-[11px] font-mono uppercase tracking-wider text-zinc-400">
                        <th class="px-5 py-3.5">{{ ucfirst($report['interval']) }} Period</th>
                        <th class="px-5 py-3.5 text-center">Paid Orders</th>
                        <th class="px-5 py-3.5 text-right">Gross Revenue</th>
                        <th class="px-5 py-3.5 text-right">Average Order Value (AOV)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.04] font-mono">
                    @forelse($report['buckets'] as $bucket)
                        <tr class="hover:bg-white/[0.025] transition-colors">
                            <td class="px-5 py-3.5 text-white font-bold flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full {{ ((float)$bucket['revenue']) > 0 ? 'bg-[#FF1744]' : 'bg-zinc-700' }}"></span>
                                <span>{{ $bucket['period'] }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-center text-zinc-200">
                                <span class="px-2 py-0.5 rounded bg-white/[0.04] border border-white/[0.06]">
                                    {{ $bucket['paid_orders'] }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right font-bold text-white tabular-nums">
                                ${{ $bucket['revenue'] }}
                            </td>
                            <td class="px-5 py-3.5 text-right text-zinc-400 tabular-nums">
                                ${{ $bucket['aov'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-zinc-500 font-mono">
                                No sales transactions recorded in this selected range.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- METRIC DEFINITIONS (Collapsible) --}}
    <div class="glass-panel rounded-2xl p-4 sm:p-5 border border-white/[0.08]" x-data="{ open: false }">
        <button type="button"
                @click="open = !open" 
                class="w-full flex items-center justify-between text-[11px] font-mono text-zinc-400 hover:text-white transition-colors focus-ring">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-[#FF1744]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-semibold uppercase tracking-wider text-zinc-300">How metrics are calculated & reporting rules</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span x-text="open ? 'Hide explanations' : 'View explanations'"></span>
                <svg :class="open ? 'rotate-180' : ''" class="w-3.5 h-3.5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </button>

        <div x-show="open" x-cloak class="mt-4 pt-4 border-t border-white/[0.06]">
            @include('admin.reports._definitions')
        </div>
    </div>
</div>
@endsection
