<div 
    x-data="cartDrawer()" 
    x-cloak
    @open-cart.window="openDrawer()" 
    @cart-updated.window="fetchCart()"
    @keydown.escape.window="if (isOpen) isOpen = false"
    class="relative z-50"
>
    <!-- Backdrop Overlay -->
    <div 
        x-show="isOpen" 
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="isOpen = false"
        class="fixed inset-0 bg-black/80 backdrop-blur-md"
        aria-hidden="true"
    ></div>

    <!-- Slide-over Drawer Panel (Elevated Glass) -->
    <div 
        x-show="isOpen" 
        x-transition:enter="transform transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 max-w-full flex pl-10"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cart-drawer-title"
    >
        <div class="w-screen max-w-md glass-elevated border-l border-white/15 shadow-glass-elevated flex flex-col justify-between">
            <!-- Header -->
            <div class="p-6 border-b border-white/10 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-hoyos-red/15 border border-hoyos-red/40 flex items-center justify-center text-hoyos-red shadow-[0_0_12px_rgba(255,0,46,0.3)]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 id="cart-drawer-title" class="text-lg font-mono font-bold tracking-wider text-white uppercase">
                            Shopping Bag
                        </h2>
                    </div>
                    <span 
                        class="text-[11px] font-mono font-bold px-2.5 py-0.5 rounded-full bg-hoyos-red/15 text-hoyos-red border border-hoyos-red/30" 
                        x-text="itemsCount + (itemsCount === 1 ? ' item' : ' items')"
                    ></span>
                </div>
                <button 
                    @click="isOpen = false" 
                    class="p-2 text-white/50 hover:text-white rounded-xl hover:bg-white/[0.08] transition-colors focus-ring"
                    aria-label="Close Shopping Bag"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Items List -->
            <div class="flex-1 overflow-y-auto p-6 space-y-3.5">
                <!-- Empty State -->
                <template x-if="items.length === 0">
                    <div class="text-center py-16 space-y-4">
                        <div class="w-16 h-16 mx-auto rounded-2xl glass border border-white/10 flex items-center justify-center text-white/40">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <p class="text-white/60 text-xs font-mono uppercase tracking-wider">Your bag is currently empty.</p>
                        <a 
                            href="{{ route('category.show') }}" 
                            @click="isOpen = false"
                            class="inline-block px-5 py-2.5 rounded-xl border border-hoyos-red/50 text-hoyos-red hover:bg-hoyos-red hover:text-white transition-all text-xs font-mono font-bold uppercase tracking-wider shadow-crimson-subtle"
                        >
                            Explore Drops
                        </a>
                    </div>
                </template>

                <!-- Item Cards -->
                <template x-for="item in items" :key="item.id">
                    <div class="flex gap-4 p-4 rounded-2xl glass border border-white/10 hover:border-white/20 transition-all">
                        <img 
                            :src="item.image || '{{ asset('images/hoyos/0ffd9c02726576625184fe52b38ace6d.jpg') }}'" 
                            :alt="item.name"
                            class="w-20 h-24 object-cover rounded-xl bg-black border border-white/10 shrink-0"
                        />
                        <div class="flex-1 flex flex-col justify-between min-w-0">
                            <div>
                                <div class="flex items-start justify-between gap-2">
                                    <h3 class="text-xs font-semibold text-white line-clamp-2 leading-snug truncate" x-text="item.name"></h3>
                                    <button 
                                        @click="removeItem(item.id)" 
                                        class="text-white/40 hover:text-hoyos-red transition-colors p-1 -mr-1"
                                        title="Remove item"
                                        aria-label="Remove item"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="mt-1 flex items-center gap-2">
                                    <span class="px-2 py-0.5 text-[10px] font-mono font-bold rounded-md bg-white/[0.08] border border-white/10 text-white" x-text="item.size"></span>
                                    <span class="text-xs font-mono text-white/60" x-text="'$' + item.price.toFixed(2)"></span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between mt-3 pt-2.5 border-t border-white/[0.08]">
                                <!-- Quantity Stepper -->
                                <div class="flex items-center glass border border-white/15 rounded-xl overflow-hidden">
                                    <button 
                                        @click="updateQty(item.id, item.quantity - 1)" 
                                        class="px-3 py-1 text-white/60 hover:text-white hover:bg-white/[0.08] text-xs font-bold transition-colors focus-ring"
                                        aria-label="Decrease quantity"
                                    >
                                        -
                                    </button>
                                    <span class="px-2 text-xs font-mono font-bold text-white min-w-[24px] text-center" x-text="item.quantity"></span>
                                    <button 
                                        @click="updateQty(item.id, item.quantity + 1)" 
                                        class="px-3 py-1 text-white/60 hover:text-white hover:bg-white/[0.08] text-xs font-bold transition-colors focus-ring"
                                        aria-label="Increase quantity"
                                    >
                                        +
                                    </button>
                                </div>
                                <span class="text-sm font-mono font-bold text-white" x-text="'$' + item.subtotal.toFixed(2)"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Footer & Checkout CTA (Sticky) -->
            <div class="p-6 border-t border-white/10 glass-elevated space-y-4">
                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between text-white/70">
                        <span>Subtotal</span>
                        <span class="font-mono font-bold text-white text-sm" x-text="'$' + subtotal.toFixed(2)"></span>
                    </div>
                    <div class="flex items-center justify-between text-white/50">
                        <span class="flex items-center gap-1.5">
                            <span>Delivery</span>
                            <span class="text-[10px] font-mono text-white/40">(J&T Express)</span>
                        </span>
                        <span class="text-emerald-400 font-mono font-bold">FREE</span>
                    </div>
                    <div class="pt-2 border-t border-white/[0.08] flex items-center justify-between text-sm">
                        <span class="font-mono font-bold text-white uppercase tracking-wider">Total</span>
                        <span class="font-mono font-black text-xl text-white drop-shadow-[0_0_12px_rgba(255,0,46,0.6)]" x-text="'$' + subtotal.toFixed(2)"></span>
                    </div>
                </div>

                <a 
                    href="{{ route('checkout.index') }}"
                    :class="{'pointer-events-none opacity-50': items.length === 0}"
                    class="w-full flex items-center justify-center gap-2 py-3.5 px-6 rounded-2xl bg-hoyos-red hover:bg-hoyos-redHover text-white font-mono font-bold text-xs uppercase tracking-wider transition-all shadow-crimson-glow hover:shadow-crimson-glow-lg focus-ring"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span>Proceed to Checkout</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function cartDrawer() {
        return {
            isOpen: false,
            items: [],
            subtotal: 0,
            itemsCount: 0,

            init() {
                this.fetchCart();
            },

            openDrawer() {
                this.isOpen = true;
                this.fetchCart();
            },

            fetchCart() {
                fetch('{{ route('cart.index') }}', {
                    headers: { 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.cart) {
                        this.items = data.cart.items || [];
                        this.subtotal = data.cart.subtotal || 0;
                        this.itemsCount = data.cart.total_quantity || 0;
                        window.dispatchEvent(new CustomEvent('cart-count-updated', { detail: { count: this.itemsCount } }));
                    }
                })
                .catch(err => console.error('Cart fetch failed', err));
            },

            updateQty(itemId, newQty) {
                fetch(`/cart/item/${itemId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ quantity: newQty })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.cart) {
                        this.items = data.cart.items || [];
                        this.subtotal = data.cart.subtotal || 0;
                        this.itemsCount = data.cart.total_quantity || 0;
                        window.dispatchEvent(new CustomEvent('cart-count-updated', { detail: { count: this.itemsCount } }));
                    }
                });
            },

            removeItem(itemId) {
                fetch(`/cart/item/${itemId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.cart) {
                        this.items = data.cart.items || [];
                        this.subtotal = data.cart.subtotal || 0;
                        this.itemsCount = data.cart.total_quantity || 0;
                        window.dispatchEvent(new CustomEvent('cart-count-updated', { detail: { count: this.itemsCount } }));
                    }
                });
            }
        }
    }
</script>
