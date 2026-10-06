<div 
    x-data="cartDrawer()" 
    x-cloak
    @open-cart.window="openDrawer()" 
    @cart-updated.window="fetchCart()"
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
        class="fixed inset-0 bg-black/80 backdrop-blur-sm"
    ></div>

    <!-- Slide-over Drawer Panel -->
    <div 
        x-show="isOpen" 
        x-transition:enter="transform transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 max-w-full flex pl-10"
    >
        <div class="w-screen max-w-md bg-hoyos-surface border-l border-hoyos-border shadow-2xl flex flex-col justify-between">
            <!-- Header -->
            <div class="p-6 border-b border-hoyos-border flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-hoyos-darkred/60 border border-hoyos-red/40 flex items-center justify-center text-hoyos-red">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-display tracking-wider text-white uppercase">Shopping Bag</h2>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-hoyos-card border border-hoyos-border text-hoyos-muted" x-text="itemsCount"></span>
                </div>
                <button 
                    @click="isOpen = false" 
                    class="p-2 text-hoyos-muted hover:text-white rounded-lg hover:bg-hoyos-card transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Items List -->
            <div class="flex-1 overflow-y-auto p-6 space-y-4">
                <template x-if="items.length === 0">
                    <div class="text-center py-16 space-y-4">
                        <div class="w-16 h-16 mx-auto rounded-full bg-hoyos-card border border-hoyos-border flex items-center justify-center text-hoyos-dim">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <p class="text-hoyos-muted text-sm uppercase tracking-wider">Your bag is currently empty.</p>
                        <a 
                            href="{{ route('category.show') }}" 
                            @click="isOpen = false"
                            class="inline-block px-5 py-2 rounded-full border border-hoyos-red/60 text-hoyos-red hover:bg-hoyos-red hover:text-white transition-all text-xs font-semibold uppercase tracking-wider"
                        >
                            Explore Drops
                        </a>
                    </div>
                </template>

                <template x-for="item in items" :key="item.id">
                    <div class="flex gap-4 p-4 rounded-xl bg-hoyos-card border border-hoyos-border hover:border-hoyos-borderBright transition-all">
                        <img 
                            :src="item.image || '{{ asset('images/hoyos/0ffd9c02726576625184fe52b38ace6d.jpg') }}'" 
                            :alt="item.name"
                            class="w-20 h-24 object-cover rounded-lg bg-black border border-hoyos-border"
                        />
                        <div class="flex-1 flex flex-col justify-between">
                            <div>
                                <div class="flex items-start justify-between gap-2">
                                    <h3 class="text-sm font-semibold text-white line-clamp-1" x-text="item.name"></h3>
                                    <button 
                                        @click="removeItem(item.id)" 
                                        class="text-hoyos-dim hover:text-hoyos-red transition-colors text-xs"
                                        title="Remove"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                                <div class="mt-1 flex items-center gap-2">
                                    <span class="px-2 py-0.5 text-xs font-bold rounded bg-hoyos-darkred/70 border border-hoyos-red/40 text-hoyos-red" x-text="item.size"></span>
                                    <span class="text-xs text-hoyos-muted" x-text="'$' + item.price.toFixed(2)"></span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between mt-3 pt-2 border-t border-hoyos-border/50">
                                <div class="flex items-center border border-hoyos-border rounded-lg overflow-hidden bg-hoyos-bg">
                                    <button 
                                        @click="updateQty(item.id, item.quantity - 1)" 
                                        class="px-2.5 py-1 text-hoyos-muted hover:text-white hover:bg-hoyos-card text-xs font-bold transition-colors"
                                    >
                                        -
                                    </button>
                                    <span class="px-3 text-xs font-semibold text-white" x-text="item.quantity"></span>
                                    <button 
                                        @click="updateQty(item.id, item.quantity + 1)" 
                                        class="px-2.5 py-1 text-hoyos-muted hover:text-white hover:bg-hoyos-card text-xs font-bold transition-colors"
                                    >
                                        +
                                    </button>
                                </div>
                                <span class="text-sm font-bold text-white" x-text="'$' + item.subtotal.toFixed(2)"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Footer & Checkout CTA -->
            <div class="p-6 border-t border-hoyos-border bg-hoyos-card/50 space-y-4">
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-hoyos-muted">Subtotal</span>
                        <span class="font-bold text-white" x-text="'$' + subtotal.toFixed(2)"></span>
                    </div>
                    <div class="flex items-center justify-between text-xs text-hoyos-dim">
                        <span>Delivery (J&T Express)</span>
                        <span class="text-hoyos-red font-semibold">FREE</span>
                    </div>
                    <div class="pt-2 border-t border-hoyos-border flex items-center justify-between text-base">
                        <span class="font-bold text-white uppercase tracking-wider">Total</span>
                        <span class="font-black text-xl text-white drop-shadow-[0_0_8px_rgba(255,0,46,0.5)]" x-text="'$' + subtotal.toFixed(2)"></span>
                    </div>
                </div>

                <a 
                    href="{{ route('checkout.index') }}"
                    :class="{'pointer-events-none opacity-50': items.length === 0}"
                    class="w-full flex items-center justify-center gap-2 py-3.5 px-6 rounded-full bg-hoyos-red hover:bg-hoyos-redHover text-white font-bold text-sm tracking-wider uppercase transition-all shadow-crimson-glow hover:shadow-[0_0_30px_rgba(255,0,46,0.6)]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Proceed to Checkout
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
