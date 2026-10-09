<x-admin-layout title="Customer Sales Invoices & Receipts">
    <div class="space-y-6">

        <!-- Header Section using shared PageHeader -->
        <x-ui.page-header
            eyebrow="Billing & Financial Audit"
            title="Customer Sales Invoices & Receipts"
            subtitle="Official tax invoices, transaction receipts, carrier delivery logs, and customer billing histories."
        >
            <x-slot:status>
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-mono bg-white/[0.04] border border-white/[0.1] text-zinc-300 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shadow-[0_0_8px_#34D399]"></span>
                    <span>Total Records: <strong class="text-white">{{ $orders->total() ?? count($orders) }}</strong></span>
                </span>
            </x-slot:status>
        </x-ui.page-header>

        <!-- KPI StatCards Row (Single Grouped Query) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
            <!-- 1. Total Invoiced -->
            <x-ui.stat-card
                title="Total Invoices"
                :value="$invoiceCounts->total_invoices ?? 0"
                subtext="All billed order records"
                :href="route('admin.invoices.index')"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>

            <!-- 2. Paid & Settled -->
            <x-ui.stat-card
                title="Paid & Settled"
                :value="$invoiceCounts->paid_invoices ?? 0"
                subtext="Completed payment receipts"
                :href="route('admin.invoices.index', ['payment_status' => 'paid'])"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>

            <!-- 3. Pending Settlement -->
            <x-ui.stat-card
                title="Pending Settlement"
                :value="$invoiceCounts->pending_invoices ?? 0"
                subtext="Awaiting customer payment"
                :href="route('admin.invoices.index', ['payment_status' => 'pending'])"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>

            <!-- 4. Refunded Receipts -->
            <x-ui.stat-card
                title="Refunded Receipts"
                :value="$invoiceCounts->refunded_invoices ?? 0"
                subtext="Off-platform / recorded refunds"
                :href="route('admin.invoices.index', ['payment_status' => 'refunded'])"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H4m0 0l4-4m-4 4l4 4m12 4v1a3 3 0 01-3 3H9"/>
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>
        </div>

        <!-- Filter & Search Toolbar (Shared FilterBar) -->
        <x-ui.filter-bar
            :action="route('admin.invoices.index')"
            method="GET"
            searchPlaceholder="Search invoice #, customer name, phone, order ID..."
            searchName="search"
            :search="request('search')"
            :hasActiveFilters="request()->hasAny(['search', 'payment_status', 'carrier'])"
            :resetUrl="route('admin.invoices.index')"
        >
            <!-- Payment Status Dropdown -->
            <div class="w-full sm:w-auto min-w-[185px]">
                <select 
                    name="payment_status" 
                    class="w-full py-2 px-3 text-xs rounded-xl bg-white/[0.03] border border-white/[0.1] text-zinc-300 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all font-mono"
                >
                    <option value="" class="bg-zinc-900 text-zinc-300">All Payment Statuses</option>
                    <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Paid</option>
                    <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Pending</option>
                    <option value="refunded" {{ request('payment_status') === 'refunded' ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Refunded</option>
                    <option value="failed" {{ request('payment_status') === 'failed' ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Failed</option>
                </select>
            </div>

            <!-- Carrier Dropdown -->
            <div class="w-full sm:w-auto min-w-[170px]">
                <select 
                    name="carrier" 
                    class="w-full py-2 px-3 text-xs rounded-xl bg-white/[0.03] border border-white/[0.1] text-zinc-300 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all font-mono"
                >
                    <option value="" class="bg-zinc-900 text-zinc-300">All Carriers</option>
                    @foreach($carriers ?? [] as $carrierOption)
                        <option value="{{ $carrierOption }}" {{ request('carrier') === $carrierOption ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">
                            {{ $carrierOption }}
                        </option>
                    @endforeach
                </select>
            </div>
        </x-ui.filter-bar>

        <!-- Invoices DataTable Container -->
        <x-ui.data-table
            :empty="$orders->isEmpty()"
            emptyTitle="No invoice records found"
            emptyDescription="There are no invoice or receipt records matching your current filter criteria."
        >
            <x-slot:thead>
                <tr>
                    <th class="px-6 py-3.5">Invoice / Order #</th>
                    <th class="px-4 py-3.5">Issue Date</th>
                    <th class="px-4 py-3.5">Billed To</th>
                    <th class="px-4 py-3.5">Delivery Carrier</th>
                    <th class="px-4 py-3.5">Total Amount</th>
                    <th class="px-4 py-3.5">Payment Status</th>
                    <th class="px-6 py-3.5 text-right">Actions</th>
                </tr>
            </x-slot:thead>

            @foreach($orders as $order)
                <tr class="hover:bg-white/[0.02] transition-colors">
                    <!-- 1. Invoice / Order # (font-mono for IDs) -->
                    <td class="px-6 py-4">
                        <a 
                            href="{{ route('admin.invoices.show', $order) }}" 
                            class="font-mono text-xs font-bold text-white hover:text-hoyos-red transition-colors inline-block tracking-tight"
                        >
                            INV-{{ $order->created_at ? $order->created_at->format('Ymd') : '20261007' }}-{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}
                        </a>
                        <div class="text-[11px] font-mono text-zinc-400 hover:text-hoyos-red mt-0.5">
                            <a href="{{ route('admin.orders.show', $order) }}">Order: {{ $order->order_number ?? '#' . $order->id }}</a>
                        </div>
                    </td>

                    <!-- 2. Issue Date (font-mono for timestamps) -->
                    <td class="px-4 py-4 font-mono">
                        <div class="text-xs font-medium text-zinc-200">
                            <x-ui.time :value="$order->created_at" />
                        </div>
                    </td>

                    <!-- 3. Billed To (font-sans for customer name and text) -->
                    <td class="px-4 py-4 font-sans">
                        <div class="text-xs font-semibold text-white">
                            {{ $order->customer_name ?? $order->user->name ?? 'Guest Customer' }}
                        </div>
                        <div class="text-[11px] text-zinc-400 font-mono mt-0.5 flex items-center gap-1.5 flex-wrap">
                            <span>{{ $order->customer_phone ?? $order->phone ?? $order->user->phone ?? 'No phone' }}</span>
                            @if(!empty($order->shipping_city ?? $order->province))
                                <span class="text-zinc-600">&bull;</span>
                                <span class="font-sans text-zinc-400">{{ $order->shipping_city ?? $order->province }}</span>
                            @endif
                        </div>
                        <div class="mt-1">
                            @if($order->user_id)
                                <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-mono font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    Account #{{ $order->user_id }}
                                </span>
                            @else
                                <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-mono font-medium bg-white/[0.02] text-admin-muted border border-white/[0.04]">
                                    Guest
                                </span>
                            @endif
                        </div>
                    </td>

                    <!-- 4. Delivery Carrier (font-sans name, font-mono tracking) -->
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-1.5 text-xs font-sans text-white font-medium">
                            <svg class="w-3.5 h-3.5 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                            </svg>
                            <span>{{ $order->shipment->carrier ?? $order->carrier ?? 'Standard Courier' }}</span>
                        </div>
                        <div class="mt-1">
                            @if(!empty($order->shipment->tracking_number ?? $order->tracking_number))
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono bg-white/[0.03] border border-white/[0.08] text-zinc-300">
                                    TRK: {{ $order->shipment->tracking_number ?? $order->tracking_number }}
                                </span>
                            @else
                                <span class="text-[10px] font-mono text-admin-muted italic">Awaiting Tracking</span>
                            @endif
                        </div>
                    </td>

                    <!-- 5. Total Amount (font-mono tabular-nums) -->
                    <td class="px-4 py-4 font-mono">
                        <div class="font-bold text-sm text-white tabular-nums">
                            ${{ number_format($order->total_amount ?? 0, 2) }}
                        </div>
                        <div class="text-[10px] text-admin-muted mt-0.5">
                            ${{ number_format($order->subtotal ?? $order->total_amount, 2) }} + ${{ number_format($order->shipping_fee ?? 0, 2) }}
                        </div>
                    </td>

                    <!-- 6. Payment Status (StatusBadge component + method) -->
                    <td class="px-4 py-4">
                        <x-ui.status-badge :status="$order->payment_status" size="sm" />
                        <div class="text-[10px] font-mono text-zinc-400 mt-1 font-semibold uppercase">
                            {{ $order->payment_method === 'aba_khqr' ? 'ABA KHQR' : ($order->payment_method === 'cod' ? 'COD' : ($order->payment_method ?? 'Payment')) }}
                        </div>
                    </td>

                    <!-- 7. Actions (Compact Button component) -->
                    <td class="px-6 py-4 text-right">
                        <x-ui.button 
                            :href="route('admin.invoices.show', $order)" 
                            variant="secondary" 
                            size="xs"
                        >
                            <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            <span>View / Print</span>
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
</x-admin-layout>
