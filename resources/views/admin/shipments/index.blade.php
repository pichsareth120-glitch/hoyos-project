<x-hoyos-layout title="Fulfillment & Shipments - Admin Hoyos">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 pb-6 border-b border-hoyos-border">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="w-2.5 h-2.5 rounded-full bg-hoyos-red"></span>
                    <span class="text-[10px] font-mono uppercase tracking-widest text-hoyos-red font-bold">Hoyos Backoffice</span>
                </div>
                <h1 class="text-3xl font-display text-white uppercase tracking-wider">
                    J&T Shipment & Fulfillment Manager
                </h1>
                <p class="text-xs text-hoyos-muted mt-1">
                    Enter J&T Express tracking codes and update internal fulfillment status milestones for dispatch.
                </p>
            </div>

            <!-- Status Filter Pills -->
            <div class="flex flex-wrap items-center gap-2">
                <a 
                    href="{{ route('admin.shipments.index') }}" 
                    class="px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider {{ empty($status) ? 'bg-hoyos-red text-white' : 'bg-hoyos-card text-hoyos-muted hover:text-white border border-hoyos-border' }}"
                >
                    All Orders
                </a>
                <a 
                    href="{{ route('admin.shipments.index', ['status' => 'order_confirmed']) }}" 
                    class="px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider {{ $status === 'order_confirmed' ? 'bg-hoyos-red text-white' : 'bg-hoyos-card text-hoyos-muted hover:text-white border border-hoyos-border' }}"
                >
                    Confirmed
                </a>
                <a 
                    href="{{ route('admin.shipments.index', ['status' => 'processing']) }}" 
                    class="px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider {{ $status === 'processing' ? 'bg-hoyos-red text-white' : 'bg-hoyos-card text-hoyos-muted hover:text-white border border-hoyos-border' }}"
                >
                    Processing
                </a>
                <a 
                    href="{{ route('admin.shipments.index', ['status' => 'shipped']) }}" 
                    class="px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider {{ $status === 'shipped' ? 'bg-hoyos-red text-white' : 'bg-hoyos-card text-hoyos-muted hover:text-white border border-hoyos-border' }}"
                >
                    Shipped
                </a>
                <a 
                    href="{{ route('admin.shipments.index', ['status' => 'out_for_delivery']) }}" 
                    class="px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider {{ $status === 'out_for_delivery' ? 'bg-hoyos-red text-white' : 'bg-hoyos-card text-hoyos-muted hover:text-white border border-hoyos-border' }}"
                >
                    Out for Delivery
                </a>
                <a 
                    href="{{ route('admin.shipments.index', ['status' => 'delivered']) }}" 
                    class="px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider {{ $status === 'delivered' ? 'bg-hoyos-red text-white' : 'bg-hoyos-card text-hoyos-muted hover:text-white border border-hoyos-border' }}"
                >
                    Delivered
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-xs text-emerald-400 flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Orders Fulfillment List -->
        @if($orders->isEmpty())
            <div class="bg-hoyos-surface border border-hoyos-border rounded-3xl p-12 text-center">
                <p class="text-xs text-hoyos-muted">No orders match the selected fulfillment filter.</p>
            </div>
        @else
            <div class="space-y-6">
                @foreach($orders as $order)
                    @php
                        $shipment = $order->shipment;
                        $currentStatus = $shipment ? $shipment->status : 'order_confirmed';
                    @endphp

                    <div class="bg-hoyos-surface border border-hoyos-border rounded-2xl p-6 shadow-sm">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                            <!-- Left: Order & Customer Snapshot -->
                            <div class="lg:col-span-4 space-y-3 border-b lg:border-b-0 lg:border-r border-hoyos-border pb-6 lg:pb-0 lg:pr-6">
                                <div class="flex items-center justify-between">
                                    <span class="font-mono text-base font-black text-white">#{{ $order->order_number }}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase {{ $order->payment_status === 'paid' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400' }}">
                                        {{ $order->payment_status }} • {{ strtoupper($order->payment_method) }}
                                    </span>
                                </div>

                                <div class="text-xs space-y-1 text-hoyos-muted">
                                    <p><strong class="text-white">{{ $order->customer_name }}</strong> (<span class="font-mono">{{ $order->customer_phone }}</span>)</p>
                                    <p class="text-white">{{ $order->province }}</p>
                                    <p class="text-[11px] leading-relaxed">{{ $order->shipping_address }}</p>
                                    @if($order->delivery_notes)
                                        <p class="text-[10px] text-amber-400/90 italic">Note: {{ $order->delivery_notes }}</p>
                                    @endif
                                </div>

                                <div class="pt-2 text-xs text-hoyos-dim font-mono">
                                    <span>Placed: {{ $order->created_at->format('M d, Y H:i') }}</span>
                                    <span class="block text-white font-bold">${{ number_format($order->total_amount, 2) }} USD ({{ $order->items->sum('quantity') }} items)</span>
                                </div>
                            </div>

                            <!-- Middle: Items Preview -->
                            <div class="lg:col-span-4 space-y-2">
                                <span class="text-[10px] font-mono uppercase tracking-widest text-hoyos-dim font-bold block mb-1">
                                    Package Items
                                </span>
                                <div class="space-y-2 max-h-40 overflow-y-auto pr-1">
                                    @foreach($order->items->sortBy(fn($i) => \App\Models\ProductVariant::sizeOrderIndex($i->variant_size ?? $i->size)) as $item)
                                        <div class="flex items-center gap-2.5 text-xs bg-hoyos-card p-2 rounded-lg border border-hoyos-border/50">
                                            <span class="w-5 h-5 rounded bg-hoyos-surface text-white text-[10px] font-mono font-bold flex items-center justify-center shrink-0">
                                                {{ $item->quantity }}x
                                            </span>
                                            <div class="truncate flex-1">
                                                <span class="text-white truncate block">{{ $item->product_title }}</span>
                                                <span class="text-[10px] text-hoyos-dim">Size: {{ $item->size }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Right: J&T Tracking & Status Form -->
                            <div class="lg:col-span-4 bg-hoyos-card border border-hoyos-border rounded-xl p-4">
                                <span class="text-[10px] font-mono uppercase tracking-widest text-hoyos-red font-bold block mb-3">
                                    Update J&T Dispatch
                                </span>

                                <form method="POST" action="{{ route('admin.shipments.update', $order) }}" class="space-y-3">
                                    @csrf
                                    @method('PUT')

                                    <div>
                                        <label class="block text-[11px] font-bold text-white uppercase tracking-wider mb-1">
                                            J&T Tracking Code
                                        </label>
                                        <input 
                                            type="text" 
                                            name="tracking_number" 
                                            value="{{ old('tracking_number', $shipment ? $shipment->tracking_number : '') }}"
                                            placeholder="e.g. JT123456789KH"
                                            class="w-full bg-hoyos-surface border border-hoyos-border focus:border-hoyos-red rounded-lg px-3 py-2 text-xs font-mono text-white placeholder-hoyos-dim focus:outline-none"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-white uppercase tracking-wider mb-1">
                                            Fulfillment Status
                                        </label>
                                        <select 
                                            name="status" 
                                            required
                                            class="w-full bg-hoyos-surface border border-hoyos-border focus:border-hoyos-red rounded-lg px-3 py-2 text-xs text-white focus:outline-none"
                                        >
                                            <option value="order_confirmed" {{ $currentStatus === 'order_confirmed' ? 'selected' : '' }}>1. Order Confirmed</option>
                                            <option value="processing" {{ $currentStatus === 'processing' ? 'selected' : '' }}>2. Processing</option>
                                            <option value="shipped" {{ $currentStatus === 'shipped' ? 'selected' : '' }}>3. Shipped (Handed to J&T)</option>
                                            <option value="out_for_delivery" {{ $currentStatus === 'out_for_delivery' ? 'selected' : '' }}>4. Out for Delivery</option>
                                            <option value="delivered" {{ $currentStatus === 'delivered' ? 'selected' : '' }}>5. Delivered</option>
                                        </select>
                                    </div>

                                    <div class="pt-1 flex items-center justify-between gap-2">
                                        @if($shipment && $shipment->tracking_number)
                                            <a 
                                                href="{{ route('orders.track', ['orderNumber' => $order->order_number]) }}" 
                                                target="_blank"
                                                class="text-[10px] text-hoyos-muted hover:text-white uppercase font-bold"
                                            >
                                                Preview Tracking ↗
                                            </a>
                                        @else
                                            <span></span>
                                        @endif

                                        <button 
                                            type="submit" 
                                            class="px-4 py-2 bg-hoyos-red hover:bg-hoyos-red-hover text-white text-xs font-bold uppercase tracking-wider rounded-lg transition-all shadow-glow"
                                        >
                                            Update Order
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Pagination -->
                <div class="pt-4">
                    {{ $orders->links() }}
                </div>
            </div>
        @endif
    </div>
</x-hoyos-layout>
