<x-admin-layout title="Order #{{ $order->order_number }}">
    <div class="space-y-6" x-data="{ cancelModalOpen: false, refundModalOpen: false, selectedPaymentId: null }">

        <!-- Back to Orders & Breadcrumbs -->
        <div class="flex items-center justify-between">
            <a 
                href="{{ route('admin.orders.index') }}" 
                class="inline-flex items-center gap-2 text-xs font-mono text-zinc-400 hover:text-white transition-colors group"
            >
                <span class="group-hover:-translate-x-1 transition-transform">&larr;</span>
                <span>Back to Orders List</span>
            </a>
            <div class="text-xs font-mono text-admin-muted">
                Order ID: <span class="text-zinc-300">#{{ $order->id }}</span>
            </div>
        </div>

        <!-- Refund Required Banner (If cancelled and KHQR paid) -->
        @if($order->order_status === 'cancelled' && $order->payment_method === 'aba_khqr' && $order->payment_status === 'paid')
            <div class="p-5 rounded-2xl glass-danger border border-amber-500/40 text-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-lg">
                <div class="flex items-start gap-3.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center font-bold text-base shrink-0">
                        ⚠
                    </div>
                    <div>
                        <h4 class="text-sm font-bold uppercase tracking-wider text-amber-300 font-mono">
                            Refund Required (ABA KHQR)
                        </h4>
                        <p class="text-xs text-amber-200/90 mt-0.5 font-sans leading-relaxed">
                            This order was cancelled after payment was completed. An off-platform refund must be issued to the customer, and then recorded in the Payments module.
                        </p>
                    </div>
                </div>
                <x-ui.button 
                    :href="route('admin.payments.index', ['search' => $order->order_number])"
                    variant="primary"
                    size="sm"
                    class="whitespace-nowrap shrink-0"
                >
                    <span>Record Refund in Payments</span>
                    <span>&rarr;</span>
                </x-ui.button>
            </div>
        @endif

        <!-- Order Header Card with Status Badges and Sticky Actions -->
        <x-ui.action-bar>
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-3 mb-1.5">
                        <h1 class="text-xl sm:text-2xl lg:text-3xl font-display text-white uppercase tracking-wider">
                            #{{ $order->order_number }}
                        </h1>

                        <!-- Order Status Badge -->
                        <x-ui.status-badge :status="$order->order_status" size="md" />

                        <!-- Payment Status Badge -->
                        <x-ui.status-badge :status="$order->payment_status" size="md">
                            Payment: {{ ucfirst($order->payment_status) }}
                        </x-ui.status-badge>
                    </div>

                    <div class="text-xs font-mono text-zinc-400 flex flex-wrap items-center gap-3">
                        <span>Placed: <strong class="text-zinc-200"><x-ui.time :value="$order->created_at" format="M d, Y H:i:s" /></strong></span>
                        <span class="text-zinc-600">&bull;</span>
                        <span>Method: <strong class="text-zinc-200 uppercase">{{ $order->payment_method === 'aba_khqr' ? 'ABA KHQR' : 'Cash on Delivery (COD)' }}</strong></span>
                    </div>
                </div>

                <!-- Action Buttons (State Enforced) -->
                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    <!-- Advance to Processing Button (confirmed -> processing only) -->
                    @if($order->order_status === 'confirmed')
                        @if($order->isCod() || $order->isPaid())
                            <form method="POST" action="{{ route('admin.orders.processing', $order) }}">
                                @csrf
                                @method('PUT')
                                <x-ui.button 
                                    type="submit" 
                                    variant="primary"
                                    size="md"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                    <span>Move to Processing</span>
                                </x-ui.button>
                            </form>
                        @else
                            <div class="px-3.5 py-2 rounded-xl text-xs font-mono text-amber-300 bg-amber-500/15 border border-amber-500/30 flex items-center gap-2" title="KHQR orders require payment confirmation before moving to processing">
                                <svg class="w-4 h-4 shrink-0 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Awaiting KHQR Payment</span>
                            </div>
                        @endif
                    @endif

                    <!-- Cancel Order Button (only confirmed or processing) -->
                    @if(in_array($order->order_status, ['confirmed', 'processing'], true))
                        <x-ui.button 
                            type="button" 
                            @click="cancelModalOpen = true"
                            variant="destructive"
                            size="md"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span>Cancel Order</span>
                        </x-ui.button>
                    @endif
                </div>
            </div>
        </x-ui.action-bar>

        <!-- Detail Grid: Customer Snapshot, Delivery, Shipment Snapshot -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Customer Snapshot -->
            <x-ui.glass-card variant="subtle" class="p-4 space-y-2">
                <span class="text-[10px] font-mono uppercase tracking-widest text-zinc-400 font-bold block">
                    Customer Snapshot
                </span>
                <div class="space-y-1.5 text-xs font-sans">
                    <div class="font-bold text-white text-sm">{{ $order->customer_name }}</div>
                    <div class="font-mono text-zinc-400">Phone: <span class="text-zinc-200">{{ $order->customer_phone }}</span></div>
                    @if($order->customer_email)
                        <div class="font-mono text-zinc-400">Email: <span class="text-zinc-200">{{ $order->customer_email }}</span></div>
                    @endif
                    <div class="text-zinc-400 pt-1.5 border-t border-white/[0.06]">
                        Account: 
                        @if($order->user)
                            <span class="text-emerald-400 font-mono font-medium">Registered User #{{ $order->user->id }}</span>
                        @else
                            <span class="text-admin-muted font-mono">Guest Checkout</span>
                        @endif
                    </div>
                </div>
            </x-ui.glass-card>

            <!-- Shipping Address Snapshot -->
            <x-ui.glass-card variant="subtle" class="p-4 space-y-2">
                <span class="text-[10px] font-mono uppercase tracking-widest text-zinc-400 font-bold block">
                    Delivery Destination
                </span>
                <div class="space-y-1.5 text-xs font-sans">
                    <div class="font-bold text-white">{{ $order->province }}</div>
                    <div class="text-zinc-300 leading-relaxed">{{ $order->shipping_address }}</div>
                    @if($order->delivery_notes)
                        <div class="text-amber-300/90 text-[11px] italic pt-1.5 border-t border-white/[0.06]">
                            Note: {{ $order->delivery_notes }}
                        </div>
                    @endif
                </div>
            </x-ui.glass-card>

            <!-- Shipment Status Snapshot -->
            <x-ui.glass-card variant="subtle" class="p-4 space-y-2">
                <span class="text-[10px] font-mono uppercase tracking-widest text-zinc-400 font-bold block">
                    Logistics &amp; Dispatch
                </span>
                <div class="space-y-2 text-xs font-sans">
                    @if($order->shipment)
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-white">{{ $order->shipment->carrier ?? 'J&T Express' }}</span>
                            <x-ui.status-badge :status="$order->shipment->status" size="xs" />
                        </div>
                        <div class="font-mono text-zinc-400">
                            Tracking: 
                            @if($order->shipment->tracking_number)
                                <span class="text-white font-bold">{{ $order->shipment->tracking_number }}</span>
                            @else
                                <span class="text-admin-muted italic">Not assigned yet</span>
                            @endif
                        </div>
                        <div class="pt-1">
                            <a 
                                href="{{ route('admin.shipments.index', ['status' => $order->shipment->status]) }}" 
                                class="text-[11px] font-mono text-hoyos-red hover:underline inline-flex items-center gap-1 max-sm:min-h-[40px]"
                            >
                                <span>Manage in Shipments Module</span>
                                <span>&rarr;</span>
                            </a>
                        </div>
                    @else
                        <div class="text-admin-muted italic">No shipment record found.</div>
                    @endif
                </div>
            </x-ui.glass-card>
        </div>

        <!-- Order Items & Totals Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Items Table (2 cols) -->
            <x-ui.glass-card class="lg:col-span-2 space-y-4" title="Ordered Line Items ({{ $order->items->count() }})">
                <div class="overflow-x-auto -mx-5 -mb-5 sm:-mx-6 sm:-mb-6">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-white/[0.08] bg-zinc-950/40 text-[10px] font-mono uppercase tracking-wider text-zinc-400">
                                <th class="py-3 px-5">Item Description</th>
                                <th class="py-3 px-4">Size</th>
                                <th class="py-3 px-4 text-right">Price</th>
                                <th class="py-3 px-4 text-center">Qty</th>
                                <th class="py-3 px-5 text-right">Line Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.04]">
                            @foreach($order->items as $item)
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="py-3.5 px-5 font-sans">
                                        <div class="font-semibold text-white">{{ $item->product_name ?? $item->product_title }}</div>
                                        @if($item->variant)
                                            <div class="text-[10px] font-mono text-zinc-400 mt-0.5">SKU: {{ $item->variant->sku ?? 'N/A' }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 font-mono font-bold text-white">
                                        {{ $item->variant_size ?? $item->size }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-mono text-zinc-300 tabular-nums">
                                        ${{ number_format($item->unit_price, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-mono font-bold text-white tabular-nums">
                                        {{ $item->quantity }}
                                    </td>
                                    <td class="py-3.5 px-5 text-right font-mono font-bold text-white tabular-nums">
                                        ${{ number_format($item->subtotal ?? ($item->unit_price * $item->quantity), 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.glass-card>

            <!-- Financial Totals Summary (1 col) -->
            <x-ui.glass-card title="Financial Summary" class="space-y-4">
                <div class="space-y-3 text-xs font-sans">
                    <div class="flex items-center justify-between text-zinc-300 font-mono">
                        <span class="font-sans text-zinc-400">Items Subtotal:</span>
                        <span class="text-white tabular-nums">${{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-zinc-300 font-mono">
                        <span class="font-sans text-zinc-400">Shipping Fee:</span>
                        <span class="text-white tabular-nums">${{ number_format($order->shipping_fee, 2) }}</span>
                    </div>
                    <div class="pt-3.5 border-t border-white/[0.08] flex items-center justify-between font-mono">
                        <span class="font-bold text-white font-sans text-sm">Total Amount:</span>
                        <span class="font-bold text-hoyos-red font-display text-2xl tracking-tight tabular-nums">${{ number_format($order->total_amount, 2) }}</span>
                    </div>

                    <div class="pt-4 border-t border-white/[0.06] space-y-2">
                        <div class="text-[11px] font-mono text-zinc-400 flex items-center justify-between">
                            <span>Payment Gateway:</span>
                            <strong class="text-white uppercase">{{ $order->payment_method }}</strong>
                        </div>
                        <div class="text-[11px] font-mono text-zinc-400 flex items-center justify-between">
                            <span>Payment State:</span>
                            <strong class="text-white uppercase">{{ $order->payment_status }}</strong>
                        </div>
                    </div>
                </div>
            </x-ui.glass-card>
        </div>

        <!-- Payments Ledger (All Attempts) -->
        <x-ui.glass-card title="Payments History ({{ $order->payments->count() }} attempt{{ $order->payments->count() === 1 ? '' : 's' }})" description="Direct transaction records and payment gateway attempts">
            @if($order->payments->isEmpty())
                <x-ui.empty-state title="No payment transactions recorded" description="No direct transaction records have been attached to this order yet." />
            @else
                <div class="overflow-x-auto -mx-5 -mb-5 sm:-mx-6 sm:-mb-6 mt-3">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-white/[0.08] bg-zinc-950/40 text-[10px] font-mono uppercase tracking-wider text-zinc-400">
                                <th class="py-3 px-5">ID</th>
                                <th class="py-3 px-4">Method</th>
                                <th class="py-3 px-4">Transaction ID</th>
                                <th class="py-3 px-4">Amount</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Created / Expires</th>
                                <th class="py-3 px-5">Paid At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.04]">
                            @foreach($order->payments as $payment)
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="py-3.5 px-5 font-mono text-white">#{{ $payment->id }}</td>
                                    <td class="py-3.5 px-4 font-mono uppercase text-zinc-300">{{ $payment->payment_method }}</td>
                                    <td class="py-3.5 px-4 font-mono text-zinc-200">{{ $payment->transaction_id ?? '—' }}</td>
                                    <td class="py-3.5 px-4 font-mono font-bold text-white tabular-nums">${{ number_format($payment->amount, 2) }}</td>
                                    <td class="py-3.5 px-4">
                                        <x-ui.status-badge :status="$payment->status" size="xs" />
                                    </td>
                                    <td class="py-3.5 px-4 font-mono text-zinc-400 text-[11px]">
                                        <div>Created: <x-ui.time :value="$payment->created_at" format="M d, H:i" /></div>
                                        @if($payment->expires_at)
                                            <div>Expires: <x-ui.time :value="$payment->expires_at" format="M d, H:i" /></div>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-5 font-mono text-[11px] text-zinc-300">
                                        <x-ui.time :value="$payment->paid_at" format="M d, Y H:i" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.glass-card>

        <!-- Status Transitions Timeline & Stock Adjustments Ledger -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Order Status History Timeline -->
            <x-ui.glass-card title="Order Status History Timeline" description="Chronological record of state transitions">
                @if($order->orderStatusHistories->isEmpty())
                    <div class="text-xs text-admin-muted italic p-4 text-center font-sans">No transitions recorded yet.</div>
                @else
                    <div class="space-y-3 mt-2">
                        @foreach($order->orderStatusHistories->sortByDesc('created_at') as $history)
                            <div class="p-3.5 bg-white/[0.02] rounded-xl border border-white/[0.06] text-xs space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <div class="font-mono text-[11px] flex items-center gap-1.5">
                                        <span class="text-admin-muted">{{ $history->old_status ?? 'start' }}</span>
                                        <span class="text-hoyos-red font-bold">&rarr;</span>
                                        <span class="text-white font-bold uppercase">{{ $history->new_status }}</span>
                                    </div>
                                    <span class="text-[10px] font-mono text-admin-muted">
                                        <x-ui.time :value="$history->created_at" format="M d, Y H:i:s" />
                                    </span>
                                </div>
                                @if($history->notes)
                                    <div class="text-zinc-300 text-[11px] leading-relaxed font-sans">
                                        {{ $history->notes }}
                                    </div>
                                @endif
                                <div class="text-[10px] font-mono text-admin-muted">
                                    Triggered by: <span class="text-zinc-400">{{ $history->admin ? $history->admin->name . ' (Admin)' : 'System' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.glass-card>

            <!-- Stock Adjustments Ledger -->
            <x-ui.glass-card title="Inventory Stock Ledger (This Order)" description="Audit trail of physical stock changes">
                @if($order->stockAdjustments->isEmpty())
                    <div class="text-xs text-admin-muted italic p-4 text-center font-sans">No stock adjustments recorded for this order.</div>
                @else
                    <div class="space-y-3 mt-2">
                        @foreach($order->stockAdjustments->sortByDesc('created_at') as $adjustment)
                            <div class="p-3.5 bg-white/[0.02] rounded-xl border border-white/[0.06] text-xs space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="font-bold text-white font-sans">
                                        {{ $adjustment->variant?->product?->name ?? 'Variant #' . $adjustment->product_variant_id }}
                                        <span class="font-mono text-zinc-400 font-normal">({{ $adjustment->variant?->size ?? 'N/A' }})</span>
                                    </div>
                                    <span class="font-mono font-bold text-xs px-2 py-0.5 rounded {{ $adjustment->quantity_change < 0 ? 'bg-hoyos-red/15 text-hoyos-red border border-hoyos-red/30' : 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' }}">
                                        {{ $adjustment->quantity_change > 0 ? '+' : '' }}{{ $adjustment->quantity_change }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-[11px] font-mono text-zinc-400">
                                    <span>Type: <strong class="text-zinc-200">{{ $adjustment->adjustment_type }}</strong></span>
                                    <span>Stock: {{ $adjustment->quantity_before }} &rarr; {{ $adjustment->quantity_after }}</span>
                                </div>
                                @if($adjustment->reason)
                                    <div class="text-[10px] text-zinc-400 italic leading-tight font-sans">
                                        {{ $adjustment->reason }}
                                    </div>
                                @endif
                                <div class="text-[9px] font-mono text-admin-muted flex items-center justify-between pt-1.5 border-t border-white/[0.04]">
                                    <span>{{ $adjustment->admin ? $adjustment->admin->name : 'System' }}</span>
                                    <span><x-ui.time :value="$adjustment->created_at" format="M d, Y H:i" /></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.glass-card>
        </div>

        <!-- Cancel Confirmation Modal using ConfirmModal component -->
        <x-ui.confirm-modal
            name="cancelModalOpen"
            title="Cancel Order #{{ $order->order_number }}"
            description="Cancelling this order will release and restore the reserved inventory stock. This action cannot be undone."
            :action="route('admin.orders.cancel', $order)"
            method="POST"
            confirmText="Confirm Cancellation"
            cancelText="Nevermind"
            variant="danger"
        >
            <x-ui.form-field
                label="Cancellation Reason"
                name="reason"
                required
                helper="Minimum 5 characters required for administrative audit trail."
            >
                <textarea 
                    name="reason" 
                    rows="3" 
                    required 
                    minlength="5"
                    placeholder="Please provide a cancellation reason (minimum 5 characters)..."
                    class="w-full bg-white/[0.03] border border-white/[0.1] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-xl p-3 text-xs text-white placeholder-zinc-500 focus:outline-none font-mono"
                ></textarea>
            </x-ui.form-field>
        </x-ui.confirm-modal>

    </div>
</x-admin-layout>
