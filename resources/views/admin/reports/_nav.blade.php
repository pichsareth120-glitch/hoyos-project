@props(['currentRoute', 'dateFrom', 'dateTo', 'supportsCsv' => true])

@php
    $tz = config('hoyos.report_timezone', 'Asia/Phnom_Penh');
    $nowTz = now($tz);
    $default30Start = $nowTz->copy()->subDays(30)->toDateString();
    $todayDate = $nowTz->toDateString();
    $maxStartDate = $nowTz->copy()->subDays(365)->toDateString();

    $is30DaysActive = ($dateFrom === $default30Start && $dateTo === $todayDate) || (!request()->has('date_from') && !request()->has('date_to'));
    $isMaxRangeActive = ($dateFrom === $maxStartDate && $dateTo === $todayDate);

    try {
        $fromCarbon = \Carbon\Carbon::parse($dateFrom);
        $toCarbon = \Carbon\Carbon::parse($dateTo);
        $diffDays = $fromCarbon->diffInDays($toCarbon) + 1;
        $rangeSummary = $fromCarbon->format('M d, Y') . ' — ' . $toCarbon->format('M d, Y') . " ({$diffDays} days)";
    } catch (\Throwable $e) {
        $rangeSummary = "{$dateFrom} — {$dateTo}";
    }
@endphp

<div class="space-y-5" x-data="{ showCalcModal: false }">
    <!-- Report Section Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-white/[0.08]">
        <div>
            <!-- Eyebrow -->
            <div class="flex items-center gap-2 mb-1.5">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#FF1744] opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-[#FF1744]"></span>
                </span>
                <span class="text-[11px] font-mono uppercase tracking-wider text-[#FF1744] font-semibold">
                    Analytics & Business Intelligence
                </span>
            </div>

            <!-- Title & Contextual Info Button -->
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white font-display uppercase">
                    Financial & Operations Reports
                </h1>
                <button 
                    type="button" 
                    @click="showCalcModal = true" 
                    class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-white/[0.04] border border-white/[0.12] hover:border-[#FF1744]/60 text-zinc-400 hover:text-white text-[11px] font-mono transition-all hover:bg-[#FF1744]/10 shadow-sm focus-ring"
                    title="How metrics are calculated"
                    aria-label="How metrics are calculated"
                >
                    ?
                </button>
            </div>
            <p class="text-xs text-zinc-400 mt-1 max-w-2xl leading-relaxed">
                Live aggregations across store performance, customer acquisition, apparel velocities, and regional deliveries.
            </p>
        </div>

        <!-- Quick Summary Badge -->
        <div class="hidden lg:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/[0.03] border border-white/[0.08] text-[11px] font-mono text-zinc-400">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
            <span>Live Local Timezone: <strong class="text-zinc-200">{{ $tz }}</strong></span>
        </div>
    </div>

    <!-- Metric Calculations Dialog Modal -->
    <div 
        x-show="showCalcModal" 
        x-cloak 
        @keydown.escape.window="showCalcModal = false"
        class="relative z-50"
    >
        <div 
            x-show="showCalcModal"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/80 backdrop-blur-md transition-opacity"
        ></div>

        <div class="fixed inset-0 z-10 overflow-y-auto p-4 sm:p-6 md:p-20 flex items-center justify-center">
            <div 
                x-show="showCalcModal"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.outside="showCalcModal = false"
                class="w-full max-w-xl bg-[#0D0D12]/95 backdrop-blur-2xl border border-white/[0.13] rounded-2xl p-6 shadow-[0_25px_50px_-12px_rgba(0,0,0,0.8)] space-y-4"
            >
                <div class="flex items-center justify-between border-b border-white/[0.08] pb-3.5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-[#FF1744]/15 border border-[#FF1744]/30 flex items-center justify-center text-[#FF1744]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xs font-mono uppercase tracking-widest text-white font-bold">
                            How Metrics Are Calculated
                        </h3>
                    </div>
                    <button 
                        type="button" 
                        @click="showCalcModal = false" 
                        class="text-zinc-400 hover:text-white transition-colors p-1 rounded-lg hover:bg-white/[0.06] text-lg leading-none"
                    >
                        &times;
                    </button>
                </div>

                <div class="text-xs text-zinc-300 font-sans space-y-3 leading-relaxed">
                    <div class="p-2.5 rounded-lg bg-white/[0.02] border border-white/[0.06]">
                        <strong class="text-white">Revenue:</strong> Sum of <code class="text-[#FF1744] font-mono px-1 py-0.5 rounded bg-white/[0.05]">orders.total_amount</code> where <code class="text-zinc-200 font-mono">payment_status = 'paid'</code> AND <code class="text-zinc-200 font-mono">order_status &lt;&gt; 'cancelled'</code>. Cancelled, refunded, pending, and expired orders are excluded.
                    </div>
                    <div class="p-2.5 rounded-lg bg-white/[0.02] border border-white/[0.06]">
                        <strong class="text-white">Order Dating:</strong> Revenue is attributed to the order's <code class="text-zinc-200 font-mono">created_at</code> timestamp.
                    </div>
                    <div class="p-2.5 rounded-lg bg-white/[0.02] border border-white/[0.06]">
                        <strong class="text-white">Pending Revenue:</strong> Non-cancelled orders with <code class="text-zinc-200 font-mono">payment_status IN ('pending', 'processing')</code>.
                    </div>
                    <div class="p-2.5 rounded-lg bg-white/[0.02] border border-white/[0.06]">
                        <strong class="text-white">Reporting Timezone:</strong> Calculated in local timezone <code class="text-white font-semibold font-mono">{{ $tz }}</code> (UTC boundaries normalized for live database queries).
                    </div>
                </div>

                <div class="pt-2 flex justify-end">
                    <button 
                        type="button" 
                        @click="showCalcModal = false" 
                        class="px-4 py-2 rounded-lg text-xs font-mono font-medium glass-btn-accent shadow-sm"
                    >
                        Got it
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Segmented Tab Navigation (shadcn inspired) -->
    <div class="glass-toolbar p-1.5 rounded-xl border border-white/[0.08] inline-flex flex-wrap items-center gap-1.5 shadow-inner">
        <a 
            href="{{ route('admin.reports.sales', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" 
            class="{{ request()->routeIs('admin.reports.sales') ? 'glass-tab-active font-semibold' : 'glass-tab-inactive' }} px-3.5 py-2 rounded-lg text-xs font-mono transition-all duration-150 flex items-center gap-2"
        >
            <svg class="w-3.5 h-3.5 {{ request()->routeIs('admin.reports.sales') ? 'text-[#FF1744]' : 'text-zinc-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Sales & Revenue</span>
        </a>
        <a 
            href="{{ route('admin.reports.products', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" 
            class="{{ request()->routeIs('admin.reports.products') ? 'glass-tab-active font-semibold' : 'glass-tab-inactive' }} px-3.5 py-2 rounded-lg text-xs font-mono transition-all duration-150 flex items-center gap-2"
        >
            <svg class="w-3.5 h-3.5 {{ request()->routeIs('admin.reports.products') ? 'text-[#FF1744]' : 'text-zinc-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
            <span>Top Products & Sizing</span>
        </a>
        <a 
            href="{{ route('admin.reports.regions', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" 
            class="{{ request()->routeIs('admin.reports.regions') ? 'glass-tab-active font-semibold' : 'glass-tab-inactive' }} px-3.5 py-2 rounded-lg text-xs font-mono transition-all duration-150 flex items-center gap-2"
        >
            <svg class="w-3.5 h-3.5 {{ request()->routeIs('admin.reports.regions') ? 'text-[#FF1744]' : 'text-zinc-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span>Regional Deliveries</span>
        </a>
        <a 
            href="{{ route('admin.reports.customers', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" 
            class="{{ request()->routeIs('admin.reports.customers') ? 'glass-tab-active font-semibold' : 'glass-tab-inactive' }} px-3.5 py-2 rounded-lg text-xs font-mono transition-all duration-150 flex items-center gap-2"
        >
            <svg class="w-3.5 h-3.5 {{ request()->routeIs('admin.reports.customers') ? 'text-[#FF1744]' : 'text-zinc-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <span>Customer Acquisition & Loyalty</span>
        </a>
    </div>

    <!-- Cohesive Date-Range Filter Toolbar -->
    <div class="glass-panel rounded-2xl p-3.5 sm:p-4 mb-6 shadow-sm border border-white/[0.085]">
        <form method="GET" action="{{ url()->current() }}" class="flex flex-col lg:flex-row lg:items-center justify-between gap-3.5 w-full">
            <!-- Left: Inline inputs for From Date and To Date with Apply Button -->
            <div class="flex flex-wrap items-center gap-2.5">
                <div class="flex items-center gap-2 text-zinc-400">
                    <span class="w-6 h-6 rounded-md bg-white/[0.04] border border-white/[0.08] flex items-center justify-center text-zinc-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </span>
                    <span class="text-[11px] font-mono uppercase tracking-wider text-zinc-400 font-bold">
                        Filter Range:
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-zinc-500">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        <input 
                            type="date" 
                            name="date_from" 
                            value="{{ $dateFrom }}" 
                            class="glass-input h-9 text-xs rounded-lg pl-8 pr-3 font-mono focus:outline-none focus-ring"
                            aria-label="Start Date"
                        />
                    </div>

                    <span class="text-zinc-500 font-mono text-xs">to</span>

                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-zinc-500">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        <input 
                            type="date" 
                            name="date_to" 
                            value="{{ $dateTo }}" 
                            class="glass-input h-9 text-xs rounded-lg pl-8 pr-3 font-mono focus:outline-none focus-ring"
                            aria-label="End Date"
                        />
                    </div>

                    <button 
                        type="submit" 
                        class="h-9 px-4 text-xs font-semibold glass-btn-accent rounded-lg flex items-center gap-1.5 focus-ring cursor-pointer"
                    >
                        <span>Apply Range</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </div>

                <!-- Active Range Indicator Pill -->
                <div class="hidden xl:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white/[0.02] border border-white/[0.06] text-[11px] font-mono text-zinc-400">
                    <span class="text-zinc-500">Active:</span>
                    <span class="text-zinc-200">{{ $rangeSummary }}</span>
                </div>
            </div>

            <!-- Right: Quick range pills and Export CSV -->
            <div class="flex items-center flex-wrap gap-2 pt-2 lg:pt-0 border-t lg:border-t-0 border-white/[0.06]">
                <a 
                    href="{{ url()->current() }}" 
                    class="h-9 px-3 text-xs font-mono font-medium rounded-lg glass-btn-subtle flex items-center gap-1.5 {{ $is30DaysActive ? 'active !text-white !border-white/[0.22] !bg-white/[0.08]' : '' }}"
                    title="Reset to rolling 30-day window"
                >
                    @if($is30DaysActive)
                        <span class="w-1.5 h-1.5 rounded-full bg-[#FF1744]"></span>
                    @endif
                    <span>Last 30 Days</span>
                </a>

                <a 
                    href="{{ url()->current() . '?' . http_build_query(['date_from' => $maxStartDate, 'date_to' => $todayDate]) }}" 
                    class="h-9 px-3 text-xs font-mono font-medium rounded-lg glass-btn-subtle flex items-center gap-1.5 {{ $isMaxRangeActive ? 'active !text-white !border-white/[0.22] !bg-white/[0.08]' : '' }}"
                    title="View up to 365 days max window"
                >
                    @if($isMaxRangeActive)
                        <span class="w-1.5 h-1.5 rounded-full bg-[#FF1744]"></span>
                    @endif
                    <span>Max Range</span>
                </a>

                @if($supportsCsv)
                    <a 
                        href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}" 
                        class="h-9 px-3.5 text-xs font-mono font-semibold rounded-lg glass-btn-subtle flex items-center gap-2 hover:!text-white hover:!border-white/[0.2] focus-ring"
                        title="Download CSV export of this report"
                    >
                        <svg class="w-3.5 h-3.5 text-[#FF1744]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Export CSV</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if(isset($errors) && $errors->any())
        <div class="p-3.5 bg-[#FF1744]/10 border border-[#FF1744]/30 rounded-xl text-xs text-[#FF365B] font-sans space-y-1 mb-6 flex items-start gap-3">
            <svg class="w-4 h-4 text-[#FF1744] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="space-y-0.5">
                @foreach($errors->all() as $err)
                    <div>{{ $err }}</div>
                @endforeach
            </div>
        </div>
    @endif
</div>
