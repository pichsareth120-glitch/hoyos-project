<div class="glass-panel rounded-xl p-4 sm:p-5 border border-white/[0.08] text-xs space-y-3">
    <div class="flex items-center gap-2.5 font-mono text-[11px] uppercase tracking-wider text-[#FF1744] font-bold">
        <div class="w-6 h-6 rounded-md bg-[#FF1744]/15 border border-[#FF1744]/30 flex items-center justify-center text-[#FF1744]">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <span>Metric Calculation Reference & Invariants</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-[11px] text-zinc-300 font-sans leading-relaxed pt-1">
        <div class="p-3 rounded-lg bg-white/[0.02] border border-white/[0.05] space-y-1">
            <div class="font-mono text-white text-xs font-semibold flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-[#FF1744]"></span>
                <span>Gross Revenue Criteria</span>
            </div>
            <p class="text-zinc-400">
                Sum of <code class="text-[#FF1744] font-mono px-1 py-0.5 rounded bg-white/[0.05]">orders.total_amount</code> where <code class="text-zinc-200 font-mono">payment_status = 'paid'</code> and <code class="text-zinc-200 font-mono">order_status &lt;&gt; 'cancelled'</code>. Excludes pending, refunded, and expired checkouts.
            </p>
        </div>

        <div class="p-3 rounded-lg bg-white/[0.02] border border-white/[0.05] space-y-1">
            <div class="font-mono text-white text-xs font-semibold flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                <span>Pending / In-Flight Revenue</span>
            </div>
            <p class="text-zinc-400">
                Sum of <code class="text-amber-400 font-mono px-1 py-0.5 rounded bg-white/[0.05]">total_amount</code> on active (non-cancelled) orders with <code class="text-zinc-200 font-mono">payment_status IN ('pending', 'processing')</code> awaiting Bakong KHQR verification or COD delivery.
            </p>
        </div>

        <div class="p-3 rounded-lg bg-white/[0.02] border border-white/[0.05] space-y-1">
            <div class="font-mono text-white text-xs font-semibold flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                <span>Order Dating & Attribution</span>
            </div>
            <p class="text-zinc-400">
                Revenue is attributed to the order's immutable <code class="text-zinc-200 font-mono">created_at</code> timestamp. Daily buckets span 00:00:00 to 23:59:59 local store time.
            </p>
        </div>

        <div class="p-3 rounded-lg bg-white/[0.02] border border-white/[0.05] space-y-1">
            <div class="font-mono text-white text-xs font-semibold flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-purple-400"></span>
                <span>Normalized Timezone Boundaries</span>
            </div>
            <p class="text-zinc-400">
                Calculated strictly in local timezone <code class="text-white font-mono font-semibold">{{ config('hoyos.report_timezone', 'Asia/Phnom_Penh') }}</code> with normalized UTC boundaries applied to underlying database queries.
            </p>
        </div>
    </div>
</div>
