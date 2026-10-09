<x-admin-layout title="Shipment & Dispatch Manager">
    <div class="space-y-6" x-data="{ showCancelConfirmModal: false, cancelFormId: '' }">

        <!-- Header Section using shared PageHeader -->
        <x-ui.page-header
            eyebrow="J&T Express Logistics"
            title="Shipment & Dispatch Manager"
            subtitle="Assign carrier tracking codes and advance physical delivery milestones."
        >
            <x-slot:status>
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-mono bg-white/[0.04] border border-white/[0.1] text-zinc-300 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shadow-[0_0_8px_#34D399]"></span>
                    <span>Total: <strong class="text-white">{{ $orders->total() ?? count($orders) }}</strong> shipments</span>
                </span>
            </x-slot:status>
        </x-ui.page-header>

        <!-- Fulfillment KPI StatCards Row (Single Grouped Query, Equal Heights) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
            <!-- 1. Confirmed / Awaiting Dispatch -->
            <x-ui.stat-card
                title="Confirmed Orders"
                :value="$shipmentCounts->confirmed ?? 0"
                subtext="Awaiting packaging & dispatch"
                :href="route('admin.shipments.index', ['status' => 'order_confirmed'])"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>

            <!-- 2. Processing in Hub -->
            <x-ui.stat-card
                title="Processing in Hub"
                :value="$shipmentCounts->processing ?? 0"
                subtext="Preparing parcels for carrier"
                :href="route('admin.shipments.index', ['status' => 'processing'])"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>

            <!-- 3. In Transit / Shipped -->
            <x-ui.stat-card
                title="In Transit / Shipped"
                :value="$shipmentCounts->in_transit ?? 0"
                subtext="Handed over to J&T Express"
                :href="route('admin.shipments.index', ['status' => 'in_transit'])"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>

            <!-- 4. Delivered -->
            <x-ui.stat-card
                title="Delivered"
                :value="$shipmentCounts->delivered ?? 0"
                subtext="Completed customer deliveries"
                :href="route('admin.shipments.index', ['status' => 'delivered'])"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>
        </div>

        <!-- Quick Status Filter Pills (Wrap cleanly at 1280px) -->
        @php
            $statusOptions = [
                ''                 => 'ALL',
                'order_confirmed'  => 'CONFIRMED',
                'processing'       => 'PROCESSING',
                'in_transit'       => 'IN TRANSIT',
                'shipped'          => 'SHIPPED',
                'out_for_delivery' => 'OUT FOR DELIVERY',
                'delivered'        => 'DELIVERED',
                'cancelled'        => 'CANCELLED',
            ];
            $hasActiveFilters = !empty($status);
        @endphp
        <div class="flex flex-wrap items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar">
            @foreach($statusOptions as $val => $label)
                @php $isActive = ($status ?? '') === $val; @endphp
                <a 
                    href="{{ $val ? route('admin.shipments.index', ['status' => $val]) : route('admin.shipments.index') }}"
                    class="{{ $isActive ? 'bg-hoyos-red text-white border-hoyos-red shadow-[0_0_16px_rgba(255,0,46,0.35)]' : 'text-zinc-300 hover:text-white bg-white/[0.03] hover:bg-white/[0.06] border border-white/[0.08] hover:border-white/[0.16]' }} max-sm:min-h-[40px] inline-flex items-center justify-center text-xs font-mono font-semibold px-3 py-1.5 sm:py-1.5 rounded-xl transition-all whitespace-nowrap"
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <!-- FilterBar Section (Wraps cleanly at 1280px) -->
        <x-ui.filter-bar
            :action="route('admin.shipments.index')"
            method="GET"
            :search="false"
            :hasActiveFilters="$hasActiveFilters"
            :resetUrl="route('admin.shipments.index')"
        >
            <div class="w-full sm:w-auto min-w-[220px] shrink-0">
                <select 
                    name="status" 
                    class="w-full py-2 px-3 text-xs rounded-xl bg-white/[0.03] border border-white/[0.1] text-zinc-300 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all font-mono"
                    onchange="this.form.submit()"
                >
                    <option value="" class="bg-zinc-900 text-zinc-300">All Delivery Statuses</option>
                    @foreach($statusOptions as $val => $label)
                        @if($val !== '')
                            <option value="{{ $val }}" {{ ($status ?? '') === $val ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">{{ $label }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
        </x-ui.filter-bar>

        <!-- Shipment Dispatch DataTable -->
        <x-ui.data-table
            :empty="$orders->isEmpty()"
            emptyTitle="No Shipments Found"
            emptyDescription="No shipments match the selected delivery status filter."
            emptyActionText="View All Active Shipments"
            :emptyActionHref="route('admin.shipments.index')"
        >
            <x-slot:thead>
                <tr>
                    <th class="px-5 py-3.5 w-[34%] min-w-[280px]">Order & Destination</th>
                    <th class="px-5 py-3.5 w-[33%] min-w-[280px]">Package Contents</th>
                    <th class="px-5 py-3.5 w-[33%] min-w-[300px]">Update J&T Dispatch</th>
                </tr>
            </x-slot:thead>

            @foreach($orders as $order)
                @php
                    $shipment = $order->shipment;
                    $currentStatus = $shipment ? $shipment->status : 'order_confirmed';
                    $totalItems = $order->items ? $order->items->sum('quantity') : 1;
                @endphp

                <tr class="align-top hover:bg-white/[0.02] transition-colors border-b border-white/[0.06]">
                    <!-- Col 1: Order & Recipient Info -->
                    <td class="p-5 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <a 
                                href="{{ route('admin.orders.show', $order) }}"
                                class="font-mono text-xs sm:text-sm font-bold text-white hover:text-hoyos-red transition-colors inline-flex items-center gap-1.5 group"
                            >
                                <span class="group-hover:underline">#{{ $order->order_number }}</span>
                                <span class="text-xs text-zinc-500 group-hover:text-hoyos-red">↗</span>
                            </a>
                            @if($order->payment_status === 'paid')
                                <x-ui.status-badge status="paid" :label="'PAID • ' . strtoupper($order->payment_method)" />
                            @else
                                <x-ui.status-badge :status="$order->payment_status" :label="strtoupper($order->payment_status) . ' • ' . strtoupper($order->payment_method)" />
                            @endif
                        </div>

                        <div class="text-xs space-y-1.5 text-zinc-300 font-sans">
                            <div>
                                <strong class="text-white text-sm font-semibold">{{ $order->customer_name }}</strong>
                                <span class="font-mono text-xs text-zinc-400 ml-1">({{ $order->customer_phone }})</span>
                            </div>
                            <div class="text-zinc-200 font-medium">{{ $order->province }}</div>
                            <div class="text-[11px] text-zinc-400 leading-relaxed">{{ $order->shipping_address }}</div>

                            @if($order->delivery_notes)
                                <div class="mt-2 p-2.5 bg-amber-500/10 border border-amber-500/20 rounded-xl text-amber-300 text-xs flex items-start gap-2">
                                    <svg class="w-4 h-4 shrink-0 mt-0.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div class="leading-relaxed font-sans">
                                        <span class="font-semibold text-amber-200">Note:</span> {{ $order->delivery_notes }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="pt-2 border-t border-white/[0.06] text-xs font-mono text-admin-muted flex items-center justify-between">
                            <span>Placed: <x-ui.time :value="$order->created_at" format="M d, Y H:i" /></span>
                            <span class="text-white font-bold tabular-nums">
                                ${{ number_format($order->total_amount, 2) }}
                                <span class="text-admin-muted font-normal">({{ $totalItems }} {{ Str::plural('item', $totalItems) }})</span>
                            </span>
                        </div>
                    </td>

                    <!-- Col 2: Package Contents -->
                    <td class="p-5 space-y-2">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-mono uppercase tracking-widest text-zinc-400 font-bold">Package Items</span>
                            <span class="text-[10px] font-mono text-admin-muted">{{ $totalItems }} total units</span>
                        </div>

                        <div class="bg-zinc-950/60 border border-white/[0.05] rounded-xl p-3 space-y-2 max-h-56 overflow-y-auto pr-1">
                            @foreach($order->items as $item)
                                @php
                                    $img = $item->variant?->product?->primaryImage?->image_path 
                                        ?? $item->variant?->product?->images?->first()?->image_path;
                                @endphp
                                <div class="flex items-center gap-3 p-2 rounded-lg bg-zinc-900/60 border border-white/[0.05] text-xs">
                                    @if($img)
                                        <img 
                                            src="{{ asset('storage/' . $img) }}" 
                                            alt="{{ $item->product_name ?? $item->product_title }}" 
                                            class="w-8 h-8 rounded-md border border-white/[0.08] object-cover bg-zinc-950 shrink-0" 
                                            onerror="this.onerror=null; this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');" 
                                        />
                                        <div class="hidden w-8 h-8 rounded-md border border-white/[0.08] bg-zinc-950 shrink-0 flex items-center justify-center text-zinc-400">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-8 h-8 rounded-md border border-white/[0.08] bg-zinc-950 shrink-0 flex items-center justify-center text-zinc-400">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <div class="text-white font-medium truncate font-sans" title="{{ $item->product_name ?? $item->product_title }}">
                                            {{ $item->product_name ?? $item->product_title }}
                                        </div>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="px-1.5 py-0.2 rounded text-[10px] font-mono uppercase bg-zinc-800 text-zinc-300 border border-white/[0.08]">
                                                Size: {{ $item->variant_size ?? $item->size ?? 'OS' }}
                                            </span>
                                            <span class="text-[10px] font-mono text-zinc-400">
                                                {{ $item->quantity }}x
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </td>

                    <!-- Col 3: Update J&T Dispatch Form -->
                    <td class="p-5">
                        <div class="bg-zinc-950/60 border border-white/[0.08] rounded-xl p-4 space-y-3" x-data="{ selectedStatus: '{{ $currentStatus }}' }">
                            <div class="flex items-center justify-between gap-2 border-b border-white/[0.05] pb-2.5">
                                <span class="text-[10px] font-mono uppercase tracking-widest text-hoyos-red font-bold block">
                                    Update J&T Dispatch
                                </span>
                                <x-ui.status-badge :status="$currentStatus" />
                            </div>

                            <form id="dispatch-form-{{ $order->id }}" method="POST" action="{{ route('admin.shipments.update', $order) }}" class="space-y-3">
                                @csrf
                                @method('PUT')

                                <x-ui.form-field label="Tracking Code" name="tracking_number">
                                    <input 
                                        type="text" 
                                        name="tracking_number"
                                        value="{{ old('tracking_number', $shipment ? $shipment->tracking_number : '') }}"
                                        placeholder="e.g. JT123456789KH"
                                        class="w-full bg-zinc-950/80 border border-zinc-800 text-zinc-100 placeholder:text-admin-muted text-xs rounded-xl p-2.5 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red font-mono transition-colors"
                                    />
                                </x-ui.form-field>

                                <x-ui.form-field label="Fulfillment Status" name="status" :required="true">
                                    <select 
                                        name="status" 
                                        x-model="selectedStatus"
                                        required
                                        class="w-full bg-zinc-950/80 border border-zinc-800 text-zinc-100 text-xs rounded-xl p-2.5 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red font-mono transition-colors"
                                        {{ $order->order_status === 'cancelled' ? 'disabled' : '' }}
                                    >
                                        <option value="order_confirmed" class="bg-zinc-900" {{ $currentStatus === 'order_confirmed' ? 'selected' : '' }}>1. Order Confirmed</option>
                                        <option value="processing" class="bg-zinc-900" {{ $currentStatus === 'processing' ? 'selected' : '' }}>2. Processing</option>
                                        <option value="shipped" class="bg-zinc-900" {{ $currentStatus === 'shipped' ? 'selected' : '' }}>3. Shipped (Handed to J&T)</option>
                                        <option value="out_for_delivery" class="bg-zinc-900" {{ $currentStatus === 'out_for_delivery' ? 'selected' : '' }}>4. Out for Delivery</option>
                                        <option value="delivered" class="bg-zinc-900" {{ $currentStatus === 'delivered' ? 'selected' : '' }}>5. Delivered</option>
                                        <option value="cancelled" class="bg-zinc-900" {{ $currentStatus === 'cancelled' ? 'selected' : '' }}>6. Cancelled</option>
                                    </select>
                                </x-ui.form-field>

                                <div class="pt-1 flex items-center justify-between gap-2">
                                    @if($shipment && $shipment->tracking_number)
                                        <a 
                                            href="{{ route('orders.track', ['orderNumber' => $order->order_number]) }}"
                                            target="_blank"
                                            class="text-[11px] text-admin-muted hover:text-zinc-300 font-mono transition-colors flex items-center gap-1 max-sm:min-h-[40px]"
                                        >
                                            <span>Preview Tracking</span>
                                            <span>↗</span>
                                        </a>
                                    @else
                                        <span></span>
                                    @endif

                                    @if($order->order_status === 'cancelled')
                                        <span class="px-3 py-1.5 bg-rose-500/10 text-rose-400 border border-rose-500/20 text-[10px] font-mono font-bold uppercase rounded-lg">
                                            Order Cancelled
                                        </span>
                                    @else
                                        <x-ui.button 
                                            type="button"
                                            variant="primary"
                                            size="sm"
                                            @click="selectedStatus === 'cancelled' ? (cancelFormId = 'dispatch-form-{{ $order->id }}', showCancelConfirmModal = true) : $el.closest('form').submit()"
                                        >
                                            Update Shipment
                                        </x-ui.button>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>

        <!-- Bottom Pagination via ActionBar -->
        @if($orders->isNotEmpty())
            <x-ui.action-bar :sticky="false">
                <x-ui.pagination :paginator="$orders" />
            </x-ui.action-bar>
        @endif

        <!-- Confirm Cancellation Modal (Global page modal) -->
        <x-ui.confirm-modal
            name="showCancelConfirmModal"
            title="Confirm Shipment Cancellation"
            description="Are you sure you want to mark this shipment as cancelled? This milestone cannot be undone."
            confirmText="Cancel Shipment"
            variant="danger"
        >
            <div class="flex items-center justify-end gap-3 pt-2">
                <x-ui.button type="button" @click="showCancelConfirmModal = false" variant="ghost" size="sm">
                    Keep Shipment
                </x-ui.button>
                <x-ui.button 
                    type="button" 
                    variant="destructive" 
                    size="sm"
                    @click="showCancelConfirmModal = false; if (cancelFormId) document.getElementById(cancelFormId).submit()"
                >
                    Confirm Cancellation
                </x-ui.button>
            </div>
        </x-ui.confirm-modal>

    </div>
</x-admin-layout>
