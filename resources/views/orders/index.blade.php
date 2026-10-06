<x-hoyos-layout title="My Orders - Hoyos">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 pb-6 border-b border-hoyos-border">
            <div>
                <h1 class="text-3xl font-display text-white uppercase tracking-wider">
                    My Orders
                </h1>
                <p class="text-xs text-hoyos-muted mt-1">
                    View your past purchases, shipment statuses, and J&T Express tracking codes.
                </p>
            </div>
            <div>
                <a 
                    href="{{ route('products.index') }}" 
                    class="px-5 py-2.5 bg-hoyos-card hover:bg-hoyos-hover border border-hoyos-border text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-colors inline-block"
                >
                    Browse Collection
                </a>
            </div>
        </div>

        @if($orders->isEmpty())
            <div class="bg-hoyos-surface border border-hoyos-border rounded-3xl p-12 text-center max-w-lg mx-auto">
                <div class="w-16 h-16 rounded-full bg-hoyos-card text-hoyos-muted flex items-center justify-center mx-auto mb-4 border border-hoyos-border">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-white uppercase tracking-[0.04em]">No Orders Found</h3>
                <p class="text-xs text-hoyos-muted mt-2 mb-6">
                    You haven't placed any orders with Hoyos yet. Discover our cyberpunk & gothic streetwear collection.
                </p>
                <a 
                    href="{{ route('products.index') }}"
                    class="px-6 py-3 bg-hoyos-red hover:bg-hoyos-red-hover text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-glow inline-block"
                >
                    Start Shopping
                </a>
            </div>
        @else
            <div class="space-y-6">
                @foreach($orders as $order)
                    <div class="bg-hoyos-surface border border-hoyos-border hover:border-hoyos-border/80 transition-colors rounded-2xl p-6 shadow-sm">
                        <!-- Order Top Bar -->
                        <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-hoyos-border/60">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="font-mono text-sm font-black text-white">#{{ $order->order_number }}</span>
                                <span class="text-xs text-hoyos-dim">•</span>
                                <span class="text-xs text-hoyos-muted">{{ $order->created_at->format('M d, Y') }}</span>
                                <span class="text-xs text-hoyos-dim">•</span>
                                <span class="text-xs font-mono font-bold text-white">${{ number_format($order->total_amount, 2) }} USD</span>
                            </div>

                            <div class="flex items-center gap-2">
                                <!-- Payment Badge -->
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider {{ $order->payment_status === 'paid' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/10 text-amber-400 border border-amber-500/30' }}">
                                    {{ $order->payment_status }}
                                </span>

                                <!-- Shipment Badge -->
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-hoyos-card border border-hoyos-border text-white">
                                    {{ $order->shipment ? $order->shipment->status_label : $order->order_status }}
                                </span>
                            </div>
                        </div>

                        <!-- Items Snapshot -->
                        <div class="py-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($order->items->sortBy(fn($i) => \App\Models\ProductVariant::sizeOrderIndex($i->variant_size ?? $i->size)) as $item)
                                <div class="flex items-center gap-3 bg-hoyos-card/50 p-2.5 rounded-xl border border-hoyos-border/40">
                                    <div class="w-12 h-14 rounded-lg bg-hoyos-card overflow-hidden shrink-0 border border-hoyos-border">
                                        <img 
                                            src="{{ $item->variant && $item->variant->product && $item->variant->product->primaryImage ? asset($item->variant->product->primaryImage->image_path) : asset('images/hoyos/placeholder-item.png') }}" 
                                            alt="{{ $item->product_title }}"
                                            class="w-full h-full object-cover"
                                        />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-white truncate">{{ $item->product_title }}</p>
                                        <p class="text-[11px] text-hoyos-muted">
                                            Size: <span class="text-white font-mono font-bold">{{ $item->size }}</span> • Qty: {{ $item->quantity }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Bottom Tracking & Actions -->
                        <div class="pt-4 border-t border-hoyos-border/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                @if($order->shipment && $order->shipment->tracking_number)
                                    <div class="flex items-center gap-2">
                                        <span class="text-[11px] text-hoyos-muted">J&T Tracking:</span>
                                        <span class="font-mono text-xs font-bold text-white bg-hoyos-card px-2.5 py-1 rounded border border-hoyos-border">
                                            {{ $order->shipment->tracking_number }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-[11px] text-hoyos-dim">Courier code pending fulfillment</span>
                                @endif
                            </div>

                            <div class="flex items-center gap-3">
                                <a 
                                    href="{{ route('orders.show', ['orderNumber' => $order->order_number]) }}" 
                                    class="px-4 py-2 bg-hoyos-card hover:bg-hoyos-hover border border-hoyos-border rounded-xl text-xs font-bold text-white uppercase tracking-wider transition-colors"
                                >
                                    Receipt Details
                                </a>
                                <a 
                                    href="{{ route('orders.track', ['orderNumber' => $order->order_number]) }}" 
                                    class="px-4 py-2 bg-hoyos-red hover:bg-hoyos-red-hover rounded-xl text-xs font-bold text-white uppercase tracking-wider transition-all shadow-glow flex items-center gap-1.5"
                                >
                                    <span>Track</span>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </a>
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
