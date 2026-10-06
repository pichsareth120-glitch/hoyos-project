<x-hoyos-layout title="Order #{{ $order->order_number }} - Hoyos">
    <div 
        x-data="{
            copied: false,
            copyText(text) {
                navigator.clipboard.writeText(text);
                this.copied = true;
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Order number copied!', type: 'success' } }));
                setTimeout(() => this.copied = false, 2000);
            }
        }"
        class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12"
    >
        <!-- Success Hero Banner -->
        <div class="bg-hoyos-surface border border-hoyos-border rounded-3xl p-8 sm:p-10 mb-10 text-center relative overflow-hidden shadow-2xl">
            <!-- Background Glow -->
            <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-96 h-96 bg-hoyos-red/15 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10">
                <!-- Checkmark Icon -->
                <div class="w-16 h-16 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-center mx-auto mb-5 shadow-glow">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <span class="inline-block text-[11px] font-mono uppercase tracking-widest text-hoyos-red font-bold mb-2">
                    {{ $order->payment_method === 'cod' ? 'Order Confirmed (Cash on Delivery)' : 'Payment Verified' }}
                </span>
                <h1 class="text-3xl sm:text-4xl font-display text-white uppercase tracking-wider mb-2">
                    Thank You for Your Order!
                </h1>
                <p class="text-xs sm:text-sm text-hoyos-muted max-w-lg mx-auto">
                    We've received your order and our fulfillment team is preparing your pieces.
                </p>

                <!-- Order Reference Bar -->
                <div class="mt-6 inline-flex flex-wrap items-center justify-center gap-3 bg-hoyos-card/80 border border-hoyos-border rounded-2xl px-5 py-3">
                    <span class="text-xs text-hoyos-muted">Order Number:</span>
                    <span class="font-mono text-sm font-black text-white">#{{ $order->order_number }}</span>
                    <button 
                        type="button" 
                        @click="copyText('{{ $order->order_number }}')"
                        class="p-1 hover:text-white text-hoyos-muted transition-colors"
                        title="Copy Order Number"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
                    <span class="text-hoyos-border">|</span>
                    <span class="text-xs text-hoyos-muted">{{ $order->created_at->format('M d, Y • h:i A') }}</span>
                </div>

                <!-- Primary CTA Links -->
                <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                    <a 
                        href="{{ route('orders.track', ['orderNumber' => $order->order_number, 'token' => $token]) }}"
                        class="px-6 py-3 bg-hoyos-red hover:bg-hoyos-red-hover text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-glow flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                        </svg>
                        Track Shipment Status
                    </a>
                    <a 
                        href="{{ route('products.index') }}"
                        class="px-6 py-3 bg-hoyos-card hover:bg-hoyos-hover border border-hoyos-border text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-colors"
                    >
                        Continue Shopping
                    </a>
                </div>
            </div>
        </div>

        <!-- J&T Express Tracking Banner if Available -->
        @if($order->shipment && $order->shipment->tracking_number)
            <div class="mb-10 p-5 rounded-2xl bg-gradient-to-r from-hoyos-card to-hoyos-surface border border-hoyos-red/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-hoyos-red/10 border border-hoyos-red/30 flex items-center justify-center text-hoyos-red shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-white uppercase">{{ $order->shipment->carrier }}</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-hoyos-red/20 text-hoyos-red font-mono font-bold uppercase">{{ $order->shipment->status_label }}</span>
                        </div>
                        <p class="text-xs text-hoyos-muted font-mono mt-0.5">
                            Tracking Code: <strong class="text-white">{{ $order->shipment->tracking_number }}</strong>
                        </p>
                    </div>
                </div>
                <a 
                    href="{{ route('orders.track', ['orderNumber' => $order->order_number, 'token' => $token]) }}"
                    class="text-xs text-hoyos-red hover:underline font-bold uppercase tracking-wider flex items-center gap-1.5"
                >
                    View Timeline & Courier Portal ↗
                </a>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Left: Order Items & Delivery Summary -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Items Table -->
                <div class="bg-hoyos-surface border border-hoyos-border rounded-2xl p-6">
                    <h3 class="text-xs font-semibold text-white uppercase tracking-[0.04em] mb-4 pb-3 border-b border-hoyos-border">
                        Ordered Items ({{ $order->items->sum('quantity') }})
                    </h3>

                    <div class="divide-y divide-hoyos-border/50">
                        @foreach($order->items as $item)
                            <div class="py-4 first:pt-0 last:pb-0 flex items-center gap-4">
                                <div class="w-16 h-20 rounded-xl bg-hoyos-card border border-hoyos-border overflow-hidden shrink-0">
                                    <img 
                                        src="{{ $item->variant && $item->variant->product && $item->variant->product->primaryImage ? asset($item->variant->product->primaryImage->image_path) : asset('images/hoyos/placeholder-item.png') }}" 
                                        alt="{{ $item->product_title }}"
                                        class="w-full h-full object-cover"
                                    />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-xs font-bold text-white truncate">{{ $item->product_title }}</h4>
                                    <p class="text-[11px] text-hoyos-muted mt-0.5">
                                        Size: <span class="text-white font-mono font-bold">{{ $item->size }}</span> • Qty: <span class="text-white font-mono">{{ $item->quantity }}</span>
                                    </p>
                                    <p class="text-xs font-mono font-bold text-hoyos-red mt-1 sm:hidden">
                                        ${{ number_format($item->total_price, 2) }}
                                    </p>
                                </div>
                                <div class="text-right hidden sm:block">
                                    <p class="text-xs font-mono font-bold text-white">
                                        ${{ number_format($item->total_price, 2) }}
                                    </p>
                                    <p class="text-[10px] text-hoyos-dim font-mono">
                                        ${{ number_format($item->unit_price, 2) }} each
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Delivery & Shipping Address -->
                <div class="bg-hoyos-surface border border-hoyos-border rounded-2xl p-6">
                    <h3 class="text-xs font-semibold text-white uppercase tracking-[0.04em] mb-4 pb-3 border-b border-hoyos-border">
                        Shipping & Delivery Address
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div>
                            <span class="text-hoyos-dim block text-[10px] uppercase font-bold tracking-wider mb-1">Recipient Name</span>
                            <span class="text-white font-medium">{{ $order->customer_name }}</span>
                        </div>
                        <div>
                            <span class="text-hoyos-dim block text-[10px] uppercase font-bold tracking-wider mb-1">Phone Number</span>
                            <span class="text-white font-mono">{{ $order->customer_phone }}</span>
                        </div>
                        <div>
                            <span class="text-hoyos-dim block text-[10px] uppercase font-bold tracking-wider mb-1">Province / City</span>
                            <span class="text-white">{{ $order->province }}</span>
                        </div>
                        <div>
                            <span class="text-hoyos-dim block text-[10px] uppercase font-bold tracking-wider mb-1">Full Delivery Address</span>
                            <span class="text-white leading-relaxed">{{ $order->shipping_address }}</span>
                        </div>
                        @if($order->delivery_notes)
                            <div class="sm:col-span-2">
                                <span class="text-hoyos-dim block text-[10px] uppercase font-bold tracking-wider mb-1">Delivery Instructions</span>
                                <span class="text-hoyos-muted italic">{{ $order->delivery_notes }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Guest Account Conversion Prompt (if guest order) -->
                @guest
                    @if(!$order->user_id)
                        <div class="bg-hoyos-card border border-hoyos-red/40 rounded-2xl p-6 relative overflow-hidden">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-2 h-2 rounded-full bg-hoyos-red"></span>
                                <h3 class="text-xs font-semibold text-white uppercase tracking-[0.04em]">
                                    Save This Order To A New Hoyos Account
                                </h3>
                            </div>
                            <p class="text-xs text-hoyos-muted mb-4">
                                You placed this order as a guest. Set a password now to create a Hoyos account and view your orders, track shipments, and save delivery addresses anytime.
                            </p>

                            <form method="POST" action="{{ route('orders.convert-guest', ['orderNumber' => $order->order_number, 'token' => $token]) }}" class="space-y-4 max-w-md">
                                @csrf
                                <div>
                                    <label class="block text-[11px] font-bold text-white uppercase tracking-wider mb-1">Set Password</label>
                                    <input 
                                        type="password" 
                                        name="password" 
                                        required 
                                        placeholder="Minimum 8 characters"
                                        class="w-full bg-hoyos-surface border border-hoyos-border focus:border-hoyos-red rounded-xl px-4 py-2.5 text-xs text-white placeholder-hoyos-dim focus:outline-none"
                                    />
                                    @error('password')
                                        <p class="text-[11px] text-hoyos-red mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-white uppercase tracking-wider mb-1">Confirm Password</label>
                                    <input 
                                        type="password" 
                                        name="password_confirmation" 
                                        required 
                                        placeholder="Repeat your password"
                                        class="w-full bg-hoyos-surface border border-hoyos-border focus:border-hoyos-red rounded-xl px-4 py-2.5 text-xs text-white placeholder-hoyos-dim focus:outline-none"
                                    />
                                </div>
                                <button 
                                    type="submit" 
                                    class="py-2.5 px-6 bg-white hover:bg-gray-100 text-black text-xs font-bold uppercase tracking-wider rounded-xl transition-colors"
                                >
                                    Create Account & Link Order
                                </button>
                            </form>
                        </div>
                    @endif
                @endguest
            </div>

            <!-- Right: Order Financial Summary -->
            <div class="lg:col-span-4 space-y-6">
                <div class="bg-hoyos-surface border border-hoyos-border rounded-2xl p-6 space-y-4">
                    <h3 class="text-xs font-semibold text-white uppercase tracking-[0.04em] pb-3 border-b border-hoyos-border">
                        Order Summary
                    </h3>

                    <div class="space-y-2.5 text-xs">
                        <div class="flex justify-between text-hoyos-muted">
                            <span>Subtotal</span>
                            <span class="text-white font-mono">${{ number_format($order->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-hoyos-muted">
                            <span>Shipping (J&T Express)</span>
                            <span class="text-white font-mono">{{ $order->shipping_cost > 0 ? '$' . number_format($order->shipping_cost, 2) : 'FREE' }}</span>
                        </div>
                        <div class="pt-3 border-t border-hoyos-border flex justify-between items-baseline">
                            <span class="text-xs font-bold text-white uppercase tracking-wider">Total Paid / Due</span>
                            <span class="text-lg font-mono font-black text-hoyos-red">${{ number_format($order->total_amount, 2) }} USD</span>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-hoyos-border space-y-2 text-xs">
                        <div class="flex justify-between text-hoyos-muted">
                            <span>Payment Method:</span>
                            <span class="text-white font-bold uppercase">{{ $order->payment_method === 'cod' ? 'Cash on Delivery' : 'ABA KHQR' }}</span>
                        </div>
                        <div class="flex justify-between text-hoyos-muted">
                            <span>Payment Status:</span>
                            <span class="font-mono uppercase font-bold {{ $order->payment_status === 'paid' ? 'text-emerald-400' : 'text-amber-400' }}">
                                {{ $order->payment_status }}
                            </span>
                        </div>
                        <div class="flex justify-between text-hoyos-muted">
                            <span>Order Status:</span>
                            <span class="font-mono uppercase font-bold text-white">{{ $order->order_status }}</span>
                        </div>
                    </div>
                </div>

                <!-- Support Box -->
                <div class="bg-hoyos-card border border-hoyos-border rounded-2xl p-5 text-center">
                    <p class="text-[11px] font-bold text-white uppercase tracking-wider mb-1">Need assistance with this order?</p>
                    <p class="text-[10px] text-hoyos-muted mb-3">Our support team is available Mon-Sat via Telegram or Phone.</p>
                    <a 
                        href="https://t.me/hoyos_support" 
                        target="_blank" 
                        class="inline-flex items-center gap-1.5 text-xs text-hoyos-red font-bold uppercase tracking-wider hover:underline"
                    >
                        Contact Telegram Support ↗
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-hoyos-layout>
