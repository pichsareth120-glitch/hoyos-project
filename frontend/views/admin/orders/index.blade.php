@extends('layouts.admin')

@section('title', 'Customer Orders')

@section('content')
<div class="space-y-6">
    <!-- Header Section using shared PageHeader -->
    <x-ui.page-header
        eyebrow="Fulfillment Pipeline"
        title="Customer Orders"
        subtitle="Manage incoming streetwear orders, advance fulfillment states, and review customer records."
    >
        <x-slot:status>
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-mono bg-white/[0.04] border border-white/[0.1] text-zinc-300 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shadow-[0_0_8px_#34D399]"></span>
                <span>Total: <strong class="text-white">{{ $orders->total() ?? count($orders) }}</strong> orders</span>
            </span>
        </x-slot:status>
    </x-ui.page-header>

    <!-- Active Action Queue Notification (if navigated from Dashboard) -->
    @if(request('queue'))
        @php
            $queueLabels = [
                'awaiting_processing' => 'Orders Awaiting Processing (Confirmed, COD or Paid)',
                'khqr_awaiting_payment' => 'KHQR Orders Awaiting Payment (Pending / Expired)',
                'cod_awaiting_cash' => 'COD Orders Awaiting Cash Collection (Shipped / Delivered)',
                'refunds_due' => 'Cancelled Orders Needing Refund Record',
            ];
            $activeQueueLabel = $queueLabels[request('queue')] ?? str_replace('_', ' ', request('queue'));
        @endphp
        <div class="p-3.5 rounded-2xl glass-danger border border-hoyos-red/40 flex items-center justify-between text-xs shadow-glow">
            <div class="flex items-center gap-2.5">
                <span class="w-2 h-2 rounded-full bg-hoyos-red animate-pulse shadow-[0_0_8px_#FF002E]"></span>
                <span class="text-zinc-400 text-[11px] font-mono uppercase tracking-wider">Dashboard Action Queue:</span>
                <strong class="text-white font-mono uppercase">{{ $activeQueueLabel }}</strong>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="text-[11px] font-mono text-hoyos-red hover:text-white flex items-center gap-1 transition-colors px-2 py-1 rounded-lg hover:bg-hoyos-red/20">
                <span>Clear Queue</span>
                <span>✕</span>
            </a>
        </div>
    @endif

    <!-- Pipeline KPI StatCards Row (Linked to Quick Pipeline Tabs) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
        <x-ui.stat-card
            title="Awaiting Processing"
            :value="$pipelineCounts->awaiting_processing ?? 0"
            subtext="Ready for packing & fulfillment"
            :href="route('admin.orders.index', ['order_status' => 'processing'])"
        >
            <x-slot:icon>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card
            title="Awaiting Payment"
            :value="$pipelineCounts->awaiting_payment ?? 0"
            subtext="Pending customer settlement"
            :href="route('admin.orders.index', ['payment_status' => 'pending'])"
        >
            <x-slot:icon>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card
            title="In Transit / Shipped"
            :value="$pipelineCounts->in_transit ?? 0"
            subtext="Dispatched with carrier"
            :href="route('admin.orders.index', ['order_status' => 'shipped'])"
        >
            <x-slot:icon>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                </svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card
            title="Delivered"
            :value="$pipelineCounts->delivered ?? 0"
            subtext="Completed customer orders"
            :href="route('admin.orders.index', ['order_status' => 'delivered'])"
        >
            <x-slot:icon>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-ui.stat-card>
    </div>

    <!-- Quick Pipeline Tabs (Horizontal Glass Pill Bar) -->
    @php
        $activeStatus = request('order_status');
        $activeQueue = request('queue');
        $activePayment = request('payment_status');
        $activeMethod = request('payment_method') ?? request('method');
        $hasActiveFilters = request()->hasAny(['search', 'order_status', 'payment_status', 'payment_method', 'method', 'date_from', 'date_to', 'queue']);

        $isAll = empty($activeStatus) && empty($activeQueue) && empty($activePayment);
        $isAwaitingProcessing = $activeQueue === 'awaiting_processing' || $activeStatus === 'processing';
        $isAwaitingPayment = $activeQueue === 'khqr_awaiting_payment' || $activePayment === 'pending';
        $isShipped = $activeStatus === 'shipped';
        $isDelivered = $activeStatus === 'delivered';
        $isCancelled = $activeStatus === 'cancelled';
    @endphp
    <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar">
        <a 
            href="{{ route('admin.orders.index') }}" 
            class="{{ $isAll ? 'bg-hoyos-red text-white border border-hoyos-red shadow-[0_0_16px_rgba(255,0,46,0.35)]' : 'text-zinc-300 hover:text-white bg-white/[0.03] hover:bg-white/[0.06] border border-white/[0.08] hover:border-white/[0.16]' }} max-sm:min-h-[40px] inline-flex items-center justify-center text-xs font-mono font-semibold px-3.5 py-1.5 sm:py-1.5 rounded-xl transition-all whitespace-nowrap"
        >
            All Orders
        </a>

        <a 
            href="{{ route('admin.orders.index', ['order_status' => 'processing']) }}" 
            class="{{ $isAwaitingProcessing ? 'bg-hoyos-red text-white border border-hoyos-red shadow-[0_0_16px_rgba(255,0,46,0.35)]' : 'text-zinc-300 hover:text-white bg-white/[0.03] hover:bg-white/[0.06] border border-white/[0.08] hover:border-white/[0.16]' }} max-sm:min-h-[40px] inline-flex items-center justify-center text-xs font-mono font-semibold px-3.5 py-1.5 sm:py-1.5 rounded-xl transition-all whitespace-nowrap"
        >
            Awaiting Processing
        </a>

        <a 
            href="{{ route('admin.orders.index', ['payment_status' => 'pending']) }}" 
            class="{{ $isAwaitingPayment ? 'bg-hoyos-red text-white border border-hoyos-red shadow-[0_0_16px_rgba(255,0,46,0.35)]' : 'text-zinc-300 hover:text-white bg-white/[0.03] hover:bg-white/[0.06] border border-white/[0.08] hover:border-white/[0.16]' }} max-sm:min-h-[40px] inline-flex items-center justify-center text-xs font-mono font-semibold px-3.5 py-1.5 sm:py-1.5 rounded-xl transition-all whitespace-nowrap"
        >
            Awaiting Payment
        </a>

        <a 
            href="{{ route('admin.orders.index', ['order_status' => 'shipped']) }}" 
            class="{{ $isShipped ? 'bg-hoyos-red text-white border border-hoyos-red shadow-[0_0_16px_rgba(255,0,46,0.35)]' : 'text-zinc-300 hover:text-white bg-white/[0.03] hover:bg-white/[0.06] border border-white/[0.08] hover:border-white/[0.16]' }} max-sm:min-h-[40px] inline-flex items-center justify-center text-xs font-mono font-semibold px-3.5 py-1.5 sm:py-1.5 rounded-xl transition-all whitespace-nowrap"
        >
            In Transit / Shipped
        </a>

        <a 
            href="{{ route('admin.orders.index', ['order_status' => 'delivered']) }}" 
            class="{{ $isDelivered ? 'bg-hoyos-red text-white border border-hoyos-red shadow-[0_0_16px_rgba(255,0,46,0.35)]' : 'text-zinc-300 hover:text-white bg-white/[0.03] hover:bg-white/[0.06] border border-white/[0.08] hover:border-white/[0.16]' }} max-sm:min-h-[40px] inline-flex items-center justify-center text-xs font-mono font-semibold px-3.5 py-1.5 sm:py-1.5 rounded-xl transition-all whitespace-nowrap"
        >
            Delivered
        </a>

        <a 
            href="{{ route('admin.orders.index', ['order_status' => 'cancelled']) }}" 
            class="{{ $isCancelled ? 'bg-hoyos-red text-white border border-hoyos-red shadow-[0_0_16px_rgba(255,0,46,0.35)]' : 'text-zinc-300 hover:text-white bg-white/[0.03] hover:bg-white/[0.06] border border-white/[0.08] hover:border-white/[0.16]' }} max-sm:min-h-[40px] inline-flex items-center justify-center text-xs font-mono font-semibold px-3.5 py-1.5 sm:py-1.5 rounded-xl transition-all whitespace-nowrap"
        >
            Cancelled
        </a>
    </div>

    <!-- FilterBar Section -->
    <x-ui.filter-bar
        :action="route('admin.orders.index')"
        method="GET"
        :search="request('search')"
        searchPlaceholder="Order #, customer name, phone..."
        :hasActiveFilters="$hasActiveFilters"
        :resetUrl="route('admin.orders.index')"
    >
        <!-- Order Status -->
        <div class="w-full sm:w-auto min-w-[175px] shrink-0">
            <select 
                name="order_status" 
                class="w-full py-2 px-3 text-xs rounded-xl bg-white/[0.03] border border-white/[0.1] text-zinc-300 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all font-mono"
            >
                <option value="" class="bg-zinc-900 text-zinc-300">All Order Statuses</option>
                <option value="confirmed" {{ request('order_status') === 'confirmed' ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Confirmed</option>
                <option value="processing" {{ request('order_status') === 'processing' ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Processing</option>
                <option value="shipped" {{ request('order_status') === 'shipped' ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Shipped</option>
                <option value="delivered" {{ request('order_status') === 'delivered' ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Delivered</option>
                <option value="cancelled" {{ request('order_status') === 'cancelled' ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Cancelled</option>
            </select>
        </div>

        <!-- Payment Status -->
        <div class="w-full sm:w-auto min-w-[185px] shrink-0">
            <select 
                name="payment_status" 
                class="w-full py-2 px-3 text-xs rounded-xl bg-white/[0.03] border border-white/[0.1] text-zinc-300 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all font-mono"
            >
                <option value="" class="bg-zinc-900 text-zinc-300">All Payment Statuses</option>
                <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Paid</option>
                <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Pending</option>
                <option value="expired" {{ request('payment_status') === 'expired' ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Expired</option>
                <option value="refunded" {{ request('payment_status') === 'refunded' ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Refunded</option>
                <option value="cancelled" {{ request('payment_status') === 'cancelled' ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Cancelled</option>
            </select>
        </div>

        <!-- Payment Method -->
        <div class="w-full sm:w-auto min-w-[160px] shrink-0">
            <select 
                name="payment_method" 
                class="w-full py-2 px-3 text-xs rounded-xl bg-white/[0.03] border border-white/[0.1] text-zinc-300 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all font-mono"
            >
                <option value="" class="bg-zinc-900 text-zinc-300">All Methods</option>
                <option value="aba_khqr" {{ ($activeMethod === 'aba_khqr') ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">ABA KHQR</option>
                <option value="cod" {{ ($activeMethod === 'cod') ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Cash on Delivery</option>
            </select>
        </div>

        <!-- Date Picker Input -->
        <div class="w-full sm:w-auto min-w-[145px] shrink-0">
            <input 
                type="date" 
                name="date_from" 
                value="{{ request('date_from') }}" 
                class="w-full py-2 px-3 text-xs rounded-xl bg-white/[0.03] border border-white/[0.1] text-zinc-300 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all font-mono"
            >
        </div>
    </x-ui.filter-bar>

    <!-- Orders DataTable Container -->
    <x-ui.data-table
        :empty="$orders->isEmpty()"
        emptyTitle="No Orders Found"
        emptyDescription="There are no customer streetwear orders matching your current query parameters."
        emptyActionText="Clear Filter Criteria"
        :emptyActionHref="route('admin.orders.index')"
    >
        <x-slot:thead>
            <tr>
                <th class="px-6 py-3.5">ORDER #</th>
                <th class="px-4 py-3.5">CUSTOMER</th>
                <th class="px-4 py-3.5">PLACED AT</th>
                <th class="px-4 py-3.5">ORDER STATUS</th>
                <th class="px-4 py-3.5">PAYMENT</th>
                <th class="px-4 py-3.5">TOTAL AMOUNT</th>
                <th class="px-6 py-3.5 text-right">ACTION</th>
            </tr>
        </x-slot:thead>

        @foreach($orders as $order)
            @php
                $itemCount = $order->items ? $order->items->sum('quantity') : 1;
                $methodLabel = match($order->payment_method) {
                    'aba_khqr' => 'ABA KHQR',
                    'cod' => 'COD',
                    default => strtoupper($order->payment_method ?? 'N/A'),
                };
            @endphp
            <tr class="hover:bg-white/[0.02] transition-colors border-b border-white/[0.04] last:border-b-0">
                <!-- 1. ORDER # (font-mono for IDs) -->
                <td class="px-6 py-4">
                    <a 
                        href="{{ route('admin.orders.show', $order) }}" 
                        class="font-mono text-xs font-bold text-white hover:text-hoyos-red transition-colors inline-block tracking-tight"
                    >
                        #{{ $order->order_number }}
                    </a>
                    <div>
                        <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-mono text-zinc-400 bg-white/[0.03] border border-white/[0.06] mt-1">
                            {{ $itemCount }} {{ Str::plural('item', $itemCount) }}
                        </span>
                    </div>
                </td>

                <!-- 2. CUSTOMER (font-sans for customer name and text) -->
                <td class="px-4 py-4 font-sans">
                    <div class="text-xs font-semibold text-white">
                        {{ $order->customer_name }}
                    </div>
                    <div class="text-[11px] text-zinc-400 font-mono mt-0.5 flex items-center gap-1.5">
                        <span>{{ $order->customer_phone ?? '—' }}</span>
                        @if(!empty($order->province ?? $order->shipping_city))
                            <span class="text-zinc-600">&bull;</span>
                            <span class="truncate max-w-[140px] font-sans">{{ $order->province ?? $order->shipping_city }}</span>
                        @endif
                    </div>
                    <div>
                        @if($order->user_id)
                            <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-mono bg-white/[0.04] text-zinc-300 border border-white/[0.08] mt-1">
                                Registered
                            </span>
                        @else
                            <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-mono bg-white/[0.02] text-admin-muted border border-white/[0.04] mt-1">
                                Guest
                            </span>
                        @endif
                    </div>
                </td>

                <!-- 3. PLACED AT (font-mono timestamps) -->
                <td class="px-4 py-4 font-mono">
                    <div class="text-xs font-medium text-zinc-200">
                        <x-ui.time :value="$order->created_at" />
                    </div>
                </td>

                <!-- 4. ORDER STATUS (StatusBadge component) -->
                <td class="px-4 py-4">
                    <x-ui.status-badge :status="$order->order_status" size="sm" />
                </td>

                <!-- 5. PAYMENT (StatusBadge component + method) -->
                <td class="px-4 py-4">
                    <x-ui.status-badge :status="$order->payment_status" size="sm" />
                    <div class="text-[10px] font-mono text-zinc-400 mt-1 font-semibold">
                        {{ $methodLabel }}
                    </div>
                </td>

                <!-- 6. TOTAL AMOUNT (font-mono tabular-nums) -->
                <td class="px-4 py-4 font-mono">
                    <div class="tabular-nums font-bold text-sm text-white">
                        ${{ number_format($order->total_amount ?? 0, 2) }}
                    </div>
                    <div class="text-[10px] text-admin-muted mt-0.5">
                        ${{ number_format($order->subtotal ?? $order->total_amount, 2) }} + ${{ number_format($order->shipping_fee ?? 0, 2) }}
                    </div>
                </td>

                <!-- 7. ACTION (Compact Button component) -->
                <td class="px-6 py-4 text-right">
                    <x-ui.button :href="route('admin.orders.show', $order)" variant="secondary" size="xs">
                        <span>Details</span>
                        <span>&rarr;</span>
                    </x-ui.button>
                </td>
            </tr>
        @endforeach

        <!-- Pagination Footer -->
        <x-slot:footer>
            <x-ui.pagination :paginator="$orders" />
        </x-slot:footer>
    </x-ui.data-table>
</div>
@endsection
