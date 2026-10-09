<x-hoyos-layout title="Order #{{ $order->order_number }} - Hoyos">
    <div 
        x-data="{
            copied: false,
            copyText(text) {
                navigator.clipboard.writeText(text);
                this.copied = true;
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Order number copied to clipboard!', type: 'success' } }));
                setTimeout(() => this.copied = false, 2000);
            }
        }"
        class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10"
    >
        <!-- Success Hero Banner (Glass-Elevated with Soft Radial Aura) -->
        <div class="glass-elevated rounded-3xl p-8 sm:p-12 mb-10 text-center relative overflow-hidden shadow-glass-elevated border border-white/15">
            <!-- Radial Aura Depth Glow -->
            <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-96 h-96 bg-[#FF002E]/[0.15] rounded-full blur-[100px] pointer-events-none -z-0"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-emerald-500/[0.08] rounded-full blur-[80px] pointer-events-none -z-0"></div>

            <div class="relative z-10 max-w-2xl mx-auto">
                <!-- Animated Success Check Badge -->
                <div class="relative w-20 h-20 mx-auto mb-6 flex items-center justify-center">
                    <div class="w-20 h-20 rounded-full bg-emerald-500/15 border-2 border-emerald-500/30 text-emerald-400 flex items-center justify-center shadow-[0_0_30px_rgba(16,185,129,0.3)]">
                        <svg class="w-10 h-10 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span class="absolute -top-1 -right-1 flex h-4 w-4">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-4 w-4 bg-emerald-500"></span>
                    </span>
                </div>

                <!-- Status Pill -->
                <div class="mb-3">
                    @if($order->payment_method === 'cod')
                        <x-ui.badge variant="confirmed">
                            Order Confirmed (Cash on Delivery)
                        </x-ui.badge>
                    @elseif($order->payment_status === 'paid')
                        <x-ui.badge variant="paid" :pulse="true">
                            Payment Verified
                        </x-ui.badge>
                    @else
                        <x-ui.badge variant="pending">
                            Awaiting Payment
                        </x-ui.badge>
                    @endif
                </div>

                <h1 class="text-3xl sm:text-5xl font-display text-white uppercase tracking-wider mb-3">
                    Thank You for Your Order!
                </h1>
                <p class="text-xs sm:text-sm text-white/70 leading-relaxed max-w-lg mx-auto">
                    We've received your order and our fulfillment team is carefully preparing and packaging your pieces.
                </p>

                <!-- Order Reference Bar -->
                <div class="mt-6 inline-flex flex-wrap items-center justify-center gap-3 glass p-2.5 sm:px-5 sm:py-3 rounded-2xl border border-white/10 shadow-glass">
                    <span class="text-xs text-white/50 font-mono">Order Number:</span>
                    <span class="font-mono text-sm font-black text-white">#{{ $order->order_number }}</span>
                    <button 
                        type="button" 
                        @click="copyText('{{ $order->order_number }}')"
                        class="p-1.5 rounded-lg text-white/60 hover:text-white hover:bg-white/10 transition-colors focus-ring"
                        title="Copy Order Number"
                        aria-label="Copy Order Number"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
                    <span class="text-white/20 hidden sm:inline">|</span>
                    <span class="text-xs text-white/60 font-mono">{{ $order->created_at->format('M d, Y • h:i A') }}</span>
                </div>

                <!-- Primary Action CTAs -->
                <div class="mt-8 flex flex-wrap items-center justify-center gap-4 no-print">
                    <x-ui.button 
                        href="{{ route('orders.track', ['orderNumber' => $order->order_number, 'token' => $token]) }}"
                        variant="default"
                        size="md"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                        </svg>
                        Track Shipment Status
                    </x-ui.button>

                    <x-ui.button 
                        href="{{ route('products.index') }}"
                        variant="secondary"
                        size="md"
                    >
                        Continue Shopping
                    </x-ui.button>

                    <button 
                        type="button" 
                        onclick="window.print()" 
                        class="px-4 py-2.5 glass glass-interactive border border-white/10 hover:border-white/20 text-white/80 hover:text-white rounded-xl text-xs font-mono font-bold uppercase tracking-wider flex items-center gap-2 transition-all"
                        title="Print Order Receipt"
                    >
                        <svg class="w-4 h-4 text-white/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print Receipt
                    </button>
                </div>
            </div>
        </div>

        <!-- J&T Express Logistics Banner (if Available) -->
        @if($order->shipment && $order->shipment->tracking_number)
            <div class="mb-10 p-5 rounded-2xl glass border border-hoyos-red/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-glass">
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-xl bg-hoyos-red/15 border border-hoyos-red/30 flex items-center justify-center text-hoyos-red shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <span class="text-xs font-bold text-white uppercase font-mono">{{ $order->shipment->carrier }}</span>
                            <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-hoyos-red/20 text-hoyos-red font-mono font-bold uppercase border border-hoyos-red/30">{{ $order->shipment->status_label }}</span>
                        </div>
                        <p class="text-xs text-white/70 font-mono mt-1">
                            Tracking Code: <strong class="text-white">{{ $order->shipment->tracking_number }}</strong>
                        </p>
                    </div>
                </div>
                <a 
                    href="{{ route('orders.track', ['orderNumber' => $order->order_number, 'token' => $token]) }}"
                    class="text-xs text-hoyos-red hover:underline font-mono font-bold uppercase tracking-wider flex items-center gap-1.5 no-print"
                >
                    View Timeline & Courier Portal ↗
                </a>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Left: Order Items & Delivery Address -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Ordered Items Card -->
                <div class="glass p-6 sm:p-7 rounded-2xl border border-white/10 shadow-glass">
                    <h3 class="text-xs font-semibold text-white uppercase tracking-[0.04em] mb-5 pb-3.5 border-b border-white/10 flex items-center justify-between font-mono">
                        <span>Ordered Items ({{ $order->items->sum('quantity') }})</span>
                        <span class="text-[11px] text-white/50">Verified Order</span>
                    </h3>

                    <div class="divide-y divide-white/[0.08]">
                        @foreach($order->items->sortBy(fn($i) => \App\Models\ProductVariant::sizeOrderIndex($i->variant_size ?? $i->size)) as $item)
                            <div class="py-4 first:pt-0 last:pb-0 flex items-center gap-4">
                                <div class="w-16 h-20 rounded-xl glass-subtle border border-white/10 overflow-hidden shrink-0 flex items-center justify-center p-1">
                                    <img 
                                        src="{{ $item->variant && $item->variant->product && $item->variant->product->primaryImage ? asset($item->variant->product->primaryImage->image_path) : asset('images/hoyos/placeholder-item.png') }}" 
                                        alt="{{ $item->product_title }}"
                                        class="w-full h-full object-cover rounded-lg"
                                    />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-bold text-white truncate">{{ $item->product_title }}</h4>
                                    <div class="flex flex-wrap items-center gap-2 mt-1">
                                        <span class="font-mono text-xs px-2 py-0.5 rounded bg-white/10 text-white/90 border border-white/10">Size: {{ $item->size }}</span>
                                        <span class="text-xs text-white/70 font-mono">Qty: {{ $item->quantity }}</span>
                                    </div>
                                    <p class="text-xs font-mono font-bold text-hoyos-red mt-1.5 sm:hidden">
                                        ${{ number_format($item->total_price, 2) }}
                                    </p>
                                </div>
                                <div class="text-right hidden sm:block">
                                    <p class="text-sm font-mono font-black text-white">
                                        ${{ number_format($item->total_price, 2) }}
                                    </p>
                                    <p class="text-xs text-zinc-300 font-mono mt-0.5">
                                        ${{ number_format($item->unit_price, 2) }} each
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Shipping & Delivery Address Card -->
                <div class="glass p-6 sm:p-7 rounded-2xl border border-white/10 shadow-glass">
                    <h3 class="text-xs font-semibold text-white uppercase tracking-[0.04em] mb-5 pb-3.5 border-b border-white/10 font-mono">
                        Shipping & Delivery Address
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="p-3.5 rounded-xl glass-subtle border border-white/[0.06]">
                            <span class="text-white/50 block text-[10px] uppercase font-bold tracking-wider mb-1 font-mono">Recipient Name</span>
                            <span class="text-white font-medium text-sm">{{ $order->customer_name }}</span>
                        </div>
                        <div class="p-3.5 rounded-xl glass-subtle border border-white/[0.06]">
                            <span class="text-white/50 block text-[10px] uppercase font-bold tracking-wider mb-1 font-mono">Phone Number</span>
                            <span class="text-white font-mono text-sm font-bold">{{ $order->customer_phone }}</span>
                        </div>
                        <div class="p-3.5 rounded-xl glass-subtle border border-white/[0.06]">
                            <span class="text-white/50 block text-[10px] uppercase font-bold tracking-wider mb-1 font-mono">Province / City</span>
                            <span class="text-white font-medium text-sm">{{ $order->province }}</span>
                        </div>
                        <div class="p-3.5 rounded-xl glass-subtle border border-white/[0.06]">
                            <span class="text-white/50 block text-[10px] uppercase font-bold tracking-wider mb-1 font-mono">Courier Partner</span>
                            <span class="text-white font-medium text-sm">J&T Express Nationwide</span>
                        </div>
                        <div class="sm:col-span-2 p-3.5 rounded-xl glass-subtle border border-white/[0.06]">
                            <span class="text-white/50 block text-[10px] uppercase font-bold tracking-wider mb-1 font-mono">Full Delivery Address</span>
                            <span class="text-white leading-relaxed text-sm">{{ $order->shipping_address }}</span>
                        </div>
                        @if($order->delivery_notes)
                            <div class="sm:col-span-2 p-3.5 rounded-xl glass-subtle border border-white/[0.06]">
                                <span class="text-white/50 block text-[10px] uppercase font-bold tracking-wider mb-1 font-mono">Delivery Instructions</span>
                                <span class="text-white/80 italic text-xs">{{ $order->delivery_notes }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Guest Account Conversion Prompt (if guest order) -->
                @guest
                    @if(!$order->user_id)
                        <div class="glass p-6 rounded-2xl border border-hoyos-red/40 relative overflow-hidden shadow-glass no-print">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-2 h-2 rounded-full bg-hoyos-red animate-pulse"></span>
                                <h3 class="text-xs font-semibold text-white uppercase tracking-[0.04em] font-mono">
                                    Save This Order To A New Hoyos Account
                                </h3>
                            </div>
                            <p class="text-xs text-white/70 mb-5 leading-relaxed">
                                You placed this order as a guest. Set a password now to create a Hoyos account and view your order history, track shipments, and save delivery addresses anytime.
                            </p>

                            <form method="POST" action="{{ route('orders.convert-guest', ['orderNumber' => $order->order_number, 'token' => $token]) }}" class="space-y-4 max-w-md">
                                @csrf
                                <div>
                                    <label class="block text-xs font-bold text-white uppercase tracking-wider mb-1.5 font-mono">Set Password</label>
                                    <input 
                                        type="password" 
                                        name="password" 
                                        required 
                                        placeholder="Minimum 8 characters"
                                        class="glass-input w-full px-4 py-2.5 text-xs text-white placeholder-white/40 focus:outline-none"
                                    />
                                    @error('password')
                                        <p class="text-xs text-hoyos-red mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-white uppercase tracking-wider mb-1.5 font-mono">Confirm Password</label>
                                    <input 
                                        type="password" 
                                        name="password_confirmation" 
                                        required 
                                        placeholder="Repeat your password"
                                        class="glass-input w-full px-4 py-2.5 text-xs text-white placeholder-white/40 focus:outline-none"
                                    />
                                </div>
                                <x-ui.button type="submit" variant="default" size="sm">
                                    Create Account & Link Order
                                </x-ui.button>
                            </form>
                        </div>
                    @endif
                @endguest
            </div>

            <!-- Right: Order Financial Summary (Sticky on Desktop) -->
            <div class="lg:col-span-4 space-y-6">
                <div class="glass p-6 rounded-2xl border border-white/10 shadow-glass sticky top-24 space-y-5">
                    <h3 class="text-xs font-semibold text-white uppercase tracking-[0.04em] pb-3.5 border-b border-white/10 font-mono">
                        Order Summary
                    </h3>

                    <!-- Line Totals -->
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between text-white/70">
                            <span>Subtotal</span>
                            <span class="text-white font-mono font-medium">${{ number_format($order->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-white/70">
                            <span>Shipping (J&T Express)</span>
                            <span class="text-emerald-400 font-mono font-bold">
                                {{ $order->shipping_cost > 0 ? '$' . number_format($order->shipping_cost, 2) : 'FREE' }}
                            </span>
                        </div>
                        <div class="pt-3.5 border-t border-white/10 flex justify-between items-baseline">
                            <span class="text-xs font-bold text-white uppercase tracking-wider font-mono">
                                {{ $order->payment_status === 'paid' ? 'Total Paid' : 'Total Due' }}
                            </span>
                            <span class="text-xl font-mono font-black text-hoyos-red">
                                ${{ number_format($order->total_amount, 2) }} USD
                            </span>
                        </div>
                    </div>

                    <!-- Payment & Status Breakdown -->
                    <div class="pt-4 border-t border-white/10 space-y-3 text-xs">
                        <div class="flex justify-between items-center text-white/70">
                            <span>Payment Method:</span>
                            <span class="text-white font-mono font-bold uppercase text-[11px] bg-white/[0.06] px-2 py-0.5 rounded border border-white/10">
                                {{ $order->payment_method === 'cod' ? 'Cash on Delivery' : 'ABA KHQR' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-white/70">
                            <span>Payment Status:</span>
                            @if($order->payment_status === 'paid')
                                <x-ui.badge variant="paid">
                                    PAID
                                </x-ui.badge>
                            @else
                                <x-ui.badge variant="pending">
                                    {{ strtoupper($order->payment_status) }}
                                </x-ui.badge>
                            @endif
                        </div>

                        <!-- Simulated Demo Banner Inside Summary -->
                        @if(($order->latestPayment?->payload['is_simulated'] ?? false) || config('hoyos.demo_mode'))
                            <div class="p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center gap-2 text-amber-300">
                                <span class="text-sm">⚡</span>
                                <span class="text-[11px] font-mono font-bold uppercase tracking-wider">
                                    Simulated Demo (No Charge)
                                </span>
                            </div>
                        @endif

                        <div class="flex justify-between items-center text-white/70">
                            <span>Order Status:</span>
                            <x-ui.badge variant="confirmed">
                                {{ strtoupper($order->order_status) }}
                            </x-ui.badge>
                        </div>
                    </div>

                    <!-- Customer Support Card Inside Summary -->
                    <div class="pt-4 border-t border-white/10 text-center no-print">
                        <p class="text-xs font-bold text-white uppercase tracking-wider mb-1 font-mono">Need Assistance?</p>
                        <p class="text-[11px] text-white/50 mb-3">Support team available Mon–Sat via Telegram.</p>
                        <a 
                            href="https://t.me/hoyos_support" 
                            target="_blank" 
                            class="inline-flex items-center gap-1.5 text-xs text-hoyos-red font-mono font-bold uppercase tracking-wider hover:underline"
                        >
                            Contact Telegram Support ↗
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-hoyos-layout>
