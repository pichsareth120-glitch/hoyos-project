<x-admin-layout title="Executive Dashboard">
    @php
        // Prepare SVG Area Chart Coordinates for Last 7 Days (1.25x Peak Scale)
        $rawMax = (float) ($trend['max_revenue'] ?? 0);
        $maxRev = $rawMax > 0 ? max($rawMax * 1.25, 25.0) : 25.0;
        $chartPoints = [];
        $svgWidth = 700;
        $baselineY = 86;
        $peakY = 14;
        $usableHeight = $baselineY - $peakY; // 72

        $nonZeroDaysCount = collect($trend['days'])->filter(fn($d) => (float)($d['revenue_float'] ?? 0) > 0)->count();
        $totalPaidOrders7Days = $recentOrders->where('payment_status', 'paid')->count();
        if ($totalPaidOrders7Days === 0 && isset($metrics['paid_orders_this_month'])) {
            $totalPaidOrders7Days = (int) $metrics['paid_orders_this_month'];
        }
        $totalPaidOrders7Days = max($totalPaidOrders7Days, 1);

        foreach ($trend['days'] as $i => $day) {
            $x = 50 + ($i * 100);
            $rev = (float) $day['revenue_float'];
            $y = $maxRev > 0 ? $baselineY - round(($rev / $maxRev) * $usableHeight) : $baselineY;
            $chartPoints[] = array_merge($day, [
                'x' => $x,
                'y' => $y,
            ]);
        }

        // SVG Line Path
        $linePath = "M {$chartPoints[0]['x']},{$chartPoints[0]['y']}";
        for ($i = 1; $i < count($chartPoints); $i++) {
            $prev = $chartPoints[$i - 1];
            $curr = $chartPoints[$i];
            $midX = ($prev['x'] + $curr['x']) / 2;
            $linePath .= " C {$midX},{$prev['y']} {$midX},{$curr['y']} {$curr['x']},{$curr['y']}";
        }

        // SVG Area Path
        $first = $chartPoints[0];
        $last = end($chartPoints);
        $areaPath = "M {$first['x']},{$baselineY} L {$first['x']},{$first['y']}";
        for ($i = 1; $i < count($chartPoints); $i++) {
            $prev = $chartPoints[$i - 1];
            $curr = $chartPoints[$i];
            $midX = ($prev['x'] + $curr['x']) / 2;
            $areaPath .= " C {$midX},{$prev['y']} {$midX},{$curr['y']} {$curr['x']},{$curr['y']}";
        }
        $areaPath .= " L {$last['x']},{$baselineY} Z";
    @endphp

    <div class="space-y-8">
        <!-- Dashboard Header & Top Status Bar -->
        <x-ui.page-header
            eyebrow="Executive Overview"
            subtitle="Real-time storefront metrics, revenue velocity, and warehouse fulfillment action queues."
        >
            <x-slot:titleSlot>
                <h1 class="text-2xl sm:text-3xl font-normal tracking-wide text-white uppercase font-display leading-tight">
                    HQ Operations Dashboard
                </h1>

                <!-- Metric Calculation Rules Tooltip (?) with Glass Modal Popover -->
                <div x-data="{ open: false }" class="relative inline-flex items-center" @click.away="open = false">
                    <button 
                        type="button" 
                        @mouseenter="open = true" 
                        @mouseleave="open = false" 
                        @click="open = !open"
                        class="w-6 h-6 rounded-full border border-white/[0.12] bg-white/[0.04] hover:bg-white/[0.09] hover:border-white/[0.25] text-zinc-400 hover:text-white flex items-center justify-center text-xs font-mono transition-all cursor-pointer focus-ring"
                        aria-label="Metric calculation definitions"
                    >
                        ?
                    </button>
                    <div 
                        x-show="open" 
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                        x-cloak
                        class="absolute left-0 top-full mt-2 w-80 p-5 rounded-2xl bg-[#120407]/98 border border-white/[0.12] text-xs text-zinc-300 shadow-[0_20px_45px_-10px_rgba(0,0,0,0.85),inset_0_1px_0_0_rgba(255,255,255,0.08)] z-50 space-y-3 font-sans"
                    >
                        <div class="text-[11px] font-bold uppercase tracking-wider text-white pb-2 border-b border-white/[0.08] flex items-center justify-between">
                            <span>Metric Calculations</span>
                            <span class="text-hoyos-red text-[10px] bg-hoyos-red/10 px-1.5 py-0.5 rounded border border-hoyos-red/30">Active Rules</span>
                        </div>
                        <div class="space-y-2 text-[11px] text-zinc-400 leading-relaxed">
                            <div><strong class="text-white">Net Revenue:</strong> Sum of confirmed and paid orders, excluding cancelled, refunded, pending, and expired transactions.</div>
                            <div><strong class="text-white">Attribution:</strong> Attributed to order creation timestamp in local <span class="text-zinc-200">{{ config('hoyos.report_timezone', 'Asia/Phnom_Penh') }}</span>.</div>
                            <div><strong class="text-white">AOV Calculation:</strong> Total monthly paid revenue divided by total paid order count.</div>
                            <div><strong class="text-white">Action Queues:</strong> Live operational counts for warehouse fulfillment, KHQR settlement, and inventory thresholds.</div>
                        </div>
                    </div>
                </div>
            </x-slot:titleSlot>

            <x-slot:status>
                <x-ui.badge variant="neutral" class="px-3 py-1.5 text-xs bg-white/[0.04] border-white/[0.1] shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shadow-[0_0_8px_#34D399]"></span>
                    <span class="font-mono text-zinc-300">Live ({{ config('hoyos.report_timezone', 'Asia/Phnom_Penh') }})</span>
                </x-ui.badge>
            </x-slot:status>
        </x-ui.page-header>

        <!-- Section 1: Top KPI Metric Cards (Grid of 4) with Hover Lift & Glass Tokens -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
            <!-- Revenue Today -->
            <x-ui.stat-card
                title="Revenue Today"
                value="${{ $metrics['revenue_today'] }}"
                subtext="Paid orders placed today"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>

            <!-- Revenue This Month -->
            <x-ui.stat-card
                title="Revenue This Month"
                value="${{ $metrics['revenue_this_month'] }}"
                :trend="$metrics['mom_growth']"
                trendLabel="vs last mo (${{ $metrics['revenue_last_month'] }})"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>

            <!-- Orders Today -->
            <x-ui.stat-card
                title="Orders Today"
                :value="$metrics['orders_today']"
                subtext="Active checkouts placed today"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>

            <!-- AOV This Month -->
            <x-ui.stat-card
                title="AOV This Month"
                value="${{ $metrics['aov_this_month'] }}"
                subtext="Across {{ $metrics['paid_orders_this_month'] }} paid orders"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>
        </div>

        <!-- Section 2: Revenue Velocity Chart & Recent Orders Feed (2-Column Grid) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Column: Last 7 Days Revenue Velocity Area Chart & Quick Navigation -->
            <div class="lg:col-span-7 xl:col-span-8 space-y-5">
                <x-ui.card class="flex flex-col">
                    <x-slot:header>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-white/[0.08]">
                            <div>
                                <h3 class="text-xs font-mono font-bold uppercase tracking-wider text-white flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-hoyos-red shadow-[0_0_8px_#FF002E]"></span>
                                    <span>Last 7 Days Revenue Velocity</span>
                                </h3>
                                <p class="text-xs text-zinc-300 mt-1 font-sans">
                                    Daily paid sales trend in {{ config('hoyos.report_timezone', 'Asia/Phnom_Penh') }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                @if($nonZeroDaysCount === 1)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-sans text-zinc-300 glass border border-white/10 shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-hoyos-red"></span>
                                        <span>{{ $totalPaidOrders7Days }} paid {{ Str::plural('order', $totalPaidOrders7Days) }} in the last 7 days</span>
                                    </span>
                                @endif
                                <x-ui.badge variant="neutral" class="font-mono tabular-nums bg-white/[0.04] border-white/[0.1] px-2.5 py-1">
                                    <span class="text-zinc-400">Peak:</span>
                                    <span class="text-white font-bold">${{ number_format($trend['max_revenue'], 2) }}</span>
                                </x-ui.badge>
                            </div>
                        </div>
                    </x-slot:header>

                    <!-- Responsive SVG Area Chart with Dark Crimson Glass Glow -->
                    <div class="pt-2 pb-1 relative">
                        <div class="w-full overflow-hidden">
                            <svg viewBox="0 0 700 105" class="w-full h-28 overflow-visible" preserveAspectRatio="none">
                                <defs>
                                    <!-- Multi-stop Crimson Glass Gradient -->
                                    <linearGradient id="redGradient" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#FF002E" stop-opacity="0.35" />
                                        <stop offset="50%" stop-color="#FF002E" stop-opacity="0.10" />
                                        <stop offset="100%" stop-color="#FF002E" stop-opacity="0.00" />
                                    </linearGradient>
                                </defs>

                                <!-- Grid Lines (Fainter at white 5%) -->
                                @if($trend['max_revenue'] <= 0)
                                    <!-- Dimmed empty-state note when all values are zero -->
                                    <text x="350" y="55" text-anchor="middle" fill="#71717a" font-family="Inter, sans-serif" font-size="11" font-weight="medium">
                                        No paid sales in last 7 days &bull; Flat Baseline
                                    </text>
                                @endif

                                <!-- Horizontal Peak Reference Line -->
                                <line x1="40" y1="{{ $peakY }}" x2="660" y2="{{ $peakY }}" stroke="rgba(255, 255, 255, 0.05)" stroke-width="1" stroke-dasharray="4 4" />

                                <!-- Horizontal Baseline Line -->
                                <line x1="40" y1="{{ $baselineY }}" x2="660" y2="{{ $baselineY }}" stroke="rgba(255, 255, 255, 0.05)" stroke-width="1" />

                                <!-- Vertical Day Grid Lines -->
                                @foreach($chartPoints as $pt)
                                    <line x1="{{ $pt['x'] }}" y1="12" x2="{{ $pt['x'] }}" y2="{{ $baselineY }}" stroke="rgba(255, 255, 255, 0.04)" stroke-width="1" stroke-dasharray="3 3" />
                                @endforeach

                                <!-- Crimson Gradient Area Fill -->
                                <path d="{{ $areaPath }}" fill="url(#redGradient)" />

                                <!-- Glowing Underlay Path -->
                                <path d="{{ $linePath }}" fill="none" stroke="#FF002E" stroke-width="5" stroke-opacity="0.20" stroke-linecap="round" stroke-linejoin="round" />

                                <!-- Crisp Foreground Crimson Line -->
                                <path d="{{ $linePath }}" fill="none" stroke="#FF002E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />

                                <!-- Data Point Markers (Clean, crisp, no fuzzy blobs) -->
                                @foreach($chartPoints as $pt)
                                    @if($pt['revenue_float'] > 0)
                                        <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="4" fill="#FFFFFF" stroke="#FF002E" stroke-width="2.5" />
                                    @else
                                        <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="2.5" fill="#71717a" opacity="0.4" />
                                    @endif
                                @endforeach
                            </svg>
                        </div>

                        <!-- Interactive Tooltip Overlay Columns -->
                        <div class="absolute inset-x-0 top-2 bottom-1 flex items-stretch">
                            @foreach($chartPoints as $pt)
                                <div class="flex-1 flex flex-col items-center justify-start group relative cursor-pointer">
                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-150 pointer-events-none z-30 absolute -top-4 text-[10px] font-mono bg-[#16060c]/98 text-white px-3 py-1.5 rounded-xl border border-white/15 shadow-[0_10px_25px_rgba(0,0,0,0.8)] whitespace-nowrap tabular-nums">
                                        <div class="font-bold text-white text-xs">${{ $pt['revenue'] }}</div>
                                        <div class="text-[9px] text-zinc-300 font-sans mt-0.5">{{ $pt['orders'] }} {{ Str::plural('order', $pt['orders']) }} &bull; {{ $pt['short_date'] }}</div>
                                    </div>
                                    <div class="w-full h-full group-hover:bg-white/[0.03] transition-colors rounded-xl"></div>
                                </div>
                            @endforeach
                        </div>

                        <!-- X-Axis Labels: Coordinated Sans-Serif Columns -->
                        <div class="flex items-center justify-between pt-3 mt-1 border-t border-white/[0.08]">
                            @foreach($chartPoints as $pt)
                                <div class="flex-1 min-w-0 text-center">
                                    <div class="text-xs font-sans text-zinc-300 font-medium truncate">
                                        {{ $pt['day_name'] }}
                                    </div>
                                    <div class="text-[11px] font-sans text-zinc-400 truncate mt-0.5">
                                        {{ substr($pt['short_date'], 4) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-ui.card>

                <!-- Quick Navigation Card (Balanced 2x2 grid on >= 1024px) -->
                <x-ui.card>
                    <x-slot:header>
                        <div class="pb-3 border-b border-white/[0.08]">
                            <h3 class="text-xs font-mono uppercase tracking-wider text-zinc-300 font-bold">
                                Quick Navigation
                            </h3>
                        </div>
                    </x-slot:header>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-2.5 sm:gap-3">
                        <a href="{{ route('admin.reports.sales') }}" class="flex items-center justify-between p-3.5 rounded-xl border border-white/[0.08] bg-white/[0.02] hover:bg-white/[0.06] hover:border-white/[0.16] hover:-translate-y-0.5 transition-all duration-200 group focus-ring text-xs font-sans text-zinc-300 hover:text-white">
                            <span class="flex items-center gap-2.5 min-w-0 pr-2">
                                <span class="w-2 h-2 rounded-full bg-hoyos-red shadow-[0_0_6px_#FF002E] shrink-0"></span>
                                <span class="font-medium truncate">Sales Velocity Report</span>
                            </span>
                            <span class="text-admin-muted group-hover:text-white group-hover:translate-x-1 transition-all shrink-0">&rarr;</span>
                        </a>
                        <a href="{{ route('admin.reports.products') }}" class="flex items-center justify-between p-3.5 rounded-xl border border-white/[0.08] bg-white/[0.02] hover:bg-white/[0.06] hover:border-white/[0.16] hover:-translate-y-0.5 transition-all duration-200 group focus-ring text-xs font-sans text-zinc-300 hover:text-white">
                            <span class="flex items-center gap-2.5 min-w-0 pr-2">
                                <span class="w-2 h-2 rounded-full bg-zinc-600 shrink-0"></span>
                                <span class="font-medium truncate">Top Selling Streetwear</span>
                            </span>
                            <span class="text-admin-muted group-hover:text-white group-hover:translate-x-1 transition-all shrink-0">&rarr;</span>
                        </a>
                        <a href="{{ route('admin.reports.regions') }}" class="flex items-center justify-between p-3.5 rounded-xl border border-white/[0.08] bg-white/[0.02] hover:bg-white/[0.06] hover:border-white/[0.16] hover:-translate-y-0.5 transition-all duration-200 group focus-ring text-xs font-sans text-zinc-300 hover:text-white">
                            <span class="flex items-center gap-2.5 min-w-0 pr-2">
                                <span class="w-2 h-2 rounded-full bg-zinc-600 shrink-0"></span>
                                <span class="font-medium truncate">Regional Delivery Breakdown</span>
                            </span>
                            <span class="text-admin-muted group-hover:text-white group-hover:translate-x-1 transition-all shrink-0">&rarr;</span>
                        </a>
                        <a href="{{ route('admin.reports.customers') }}" class="flex items-center justify-between p-3.5 rounded-xl border border-white/[0.08] bg-white/[0.02] hover:bg-white/[0.06] hover:border-white/[0.16] hover:-translate-y-0.5 transition-all duration-200 group focus-ring text-xs font-sans text-zinc-300 hover:text-white">
                            <span class="flex items-center gap-2.5 min-w-0 pr-2">
                                <span class="w-2 h-2 rounded-full bg-zinc-600 shrink-0"></span>
                                <span class="font-medium truncate">Customer Intelligence</span>
                            </span>
                            <span class="text-admin-muted group-hover:text-white group-hover:translate-x-1 transition-all shrink-0">&rarr;</span>
                        </a>
                    </div>
                </x-ui.card>
            </div>

            <!-- Right Column: Recent Orders Feed -->
            <div class="lg:col-span-5 xl:col-span-4 flex flex-col h-full self-stretch">
                <!-- Recent Orders & Activity Live Card -->
                <x-ui.card class="h-full flex flex-col self-stretch">
                    <x-slot:header>
                        <div class="flex items-center justify-between pb-3.5 border-b border-white/[0.08]">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shadow-[0_0_8px_#34D399]"></span>
                                <h3 class="text-xs font-mono uppercase tracking-wider text-white font-bold">
                                    Recent Orders & Activity
                                </h3>
                            </div>
                            <span class="text-[10px] font-mono text-zinc-400 bg-white/[0.04] px-2 py-0.5 rounded border border-white/[0.08]">Live Feed</span>
                        </div>
                    </x-slot:header>

                    <div class="-mx-5 -mt-2 flex-1 flex flex-col divide-y divide-white/[0.05]">
                        @forelse($recentOrders as $order)
                            <x-ui.data-row :href="route('admin.orders.show', $order)" class="flex-1">
                                <div class="space-y-1 min-w-0 pr-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[13px] font-mono font-semibold text-white group-hover:text-hoyos-red transition-colors">
                                            #{{ $order->order_number }}
                                        </span>
                                        <x-ui.status-badge :status="$order->payment_status" size="xs">
                                            {{ ucfirst($order->payment_status) }}
                                        </x-ui.status-badge>
                                    </div>
                                    <div class="text-[11px] text-zinc-300 flex items-center gap-2 truncate font-sans">
                                        <span class="text-zinc-200 font-medium truncate">{{ $order->customer_name }}</span>
                                        <span class="text-zinc-500">&bull;</span>
                                        <span class="text-zinc-400 flex-shrink-0">{{ \App\Support\Time::diffForHumans($order->created_at) }}</span>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <div class="text-[13px] font-mono font-bold text-white tabular-nums">
                                        ${{ number_format($order->total_amount, 2) }}
                                    </div>
                                    <span class="text-[10px] font-mono text-admin-muted group-hover:text-white group-hover:translate-x-1 inline-flex items-center transition-all">
                                        &rarr;
                                    </span>
                                </div>
                            </x-ui.data-row>
                        @empty
                            <div class="p-5 flex-1 flex items-center justify-center">
                                <x-ui.empty-state title="No storefront orders recorded yet" />
                            </div>
                        @endforelse
                    </div>

                    <!-- Footer: View All Orders Link -->
                    <x-slot:footer>
                        <div class="flex items-center justify-between text-xs font-sans">
                            <span class="text-[10px] text-admin-muted font-sans">Storefront Stream</span>
                            <a href="{{ route('admin.orders.index') }}" class="text-zinc-300 hover:text-white inline-flex items-center gap-1.5 group transition-colors focus-ring rounded-lg px-2 py-1 font-sans font-medium">
                                <span>View All Orders</span>
                                <span class="group-hover:translate-x-1 transition-transform text-hoyos-red font-bold">&rarr;</span>
                            </a>
                        </div>
                    </x-slot:footer>
                </x-ui.card>
            </div>
        </div>

        <!-- Section 3: Operational Action Queues (Grid) with Idle Dimming & Red Active Tint -->
        <div class="space-y-4 pt-2">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2">
                <div>
                    <h2 class="text-sm font-mono font-bold uppercase tracking-wider text-white flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-hoyos-red shadow-[0_0_8px_#FF002E]"></span>
                        <span>Operational Action Queues</span>
                    </h2>
                    <p class="text-xs sm:text-[13px] text-zinc-300 mt-1 font-sans leading-relaxed">
                        Real-time pending items requiring staff triage, dispatch, collection, or restock
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5">
                @foreach($actionQueues as $queue)
                    <x-ui.queue-card
                        :title="$queue['title']"
                        :description="$queue['description']"
                        :count="$queue['count']"
                        :badge="$queue['badge']"
                        :link="$queue['link']"
                        :urgent="$queue['urgent']"
                    />
                @endforeach

                <!-- 8th Summary Card: Total Pending Actions (Balances 4-column grid) -->
                @php
                    $totalPendingActions = collect($actionQueues)->sum('count');
                @endphp
                <div class="rounded-2xl glass glass-hover p-5 sm:p-6 transition-all duration-200 group flex flex-col justify-between relative overflow-hidden focus-ring">
                    <div class="relative z-10 space-y-3.5">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-hoyos-red animate-pulse shadow-[0_0_8px_#FF002E]"></span>
                                <x-ui.badge variant="neutral">
                                    All Queues
                                </x-ui.badge>
                            </div>
                            <div class="tabular-nums font-mono font-bold tracking-tight text-2xl text-hoyos-red drop-shadow-[0_0_12px_rgba(255,0,46,0.5)]">
                                {{ $totalPendingActions }}
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <h3 class="text-xs font-mono font-bold text-white group-hover:text-hoyos-red transition-colors">
                                Total Pending Actions
                            </h3>
                            <p class="text-xs text-zinc-300 line-clamp-2 leading-relaxed font-sans">
                                Consolidated operational backlog across warehouse, payment, and inventory queues.
                            </p>
                        </div>
                    </div>

                    <div class="relative z-10 pt-4 mt-4 border-t border-white/[0.08] flex items-center justify-between text-xs transition-colors">
                        <span class="text-[11px] font-mono uppercase tracking-wider text-zinc-300 font-semibold">
                            Summary
                        </span>
                        <a 
                            href="{{ route('admin.orders.index') }}" 
                            class="inline-flex items-center gap-1.5 text-white font-sans font-medium hover:text-white group-hover:translate-x-1 transition-all duration-200 focus-ring rounded-lg px-2.5 py-1 bg-white/[0.06] hover:bg-white/[0.12] border border-white/10"
                        >
                            <span>View All Queues</span>
                            <span class="text-hoyos-red" aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
