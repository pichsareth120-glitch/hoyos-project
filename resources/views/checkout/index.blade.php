<x-hoyos-layout title="Checkout">
    <div 
        x-data="{
            step: 1,
            paymentMethod: 'aba_khqr',
            selectedAddressId: {{ $defaultAddress ? $defaultAddress->id : 'null' }},
            useSavedAddress: {{ $savedAddresses->isNotEmpty() ? 'true' : 'false' }},
            fillAddress(addr) {
                document.getElementById('customer_name').value = addr.recipient_name;
                document.getElementById('customer_phone').value = addr.recipient_phone;
                document.getElementById('province').value = addr.province;
                document.getElementById('shipping_address').value = addr.address_line;
                if (addr.notes) {
                    document.getElementById('delivery_notes').value = addr.notes;
                }
            }
        }"
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10"
    >
        <div class="mb-8">
            <h1 class="text-3xl font-display text-white uppercase tracking-wider">
                Checkout
            </h1>
            <p class="text-xs text-hoyos-muted mt-1">
                Complete your details to secure your pieces. Shipped safely via J&T Express.
            </p>
        </div>

        <form method="POST" action="{{ route('checkout.store') }}" class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
            @csrf

            <!-- Left: 2-Step Form -->
            <div class="lg:col-span-7 space-y-8">
                <!-- Step Stepper Tabs -->
                <div class="flex items-center gap-4 border-b border-hoyos-border pb-4">
                    <button 
                        type="button" 
                        @click="step = 1"
                        :class="step === 1 ? 'text-hoyos-red border-b-2 border-hoyos-red font-bold' : 'text-hoyos-muted'"
                        class="pb-2 text-xs uppercase tracking-wider flex items-center gap-2"
                    >
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px]" :class="step === 1 ? 'bg-hoyos-red text-white' : 'bg-hoyos-card text-hoyos-dim'">1</span>
                        Delivery Information
                    </button>
                    <span class="text-hoyos-dim">→</span>
                    <button 
                        type="button" 
                        @click="step = 2"
                        :class="step === 2 ? 'text-hoyos-red border-b-2 border-hoyos-red font-bold' : 'text-hoyos-muted'"
                        class="pb-2 text-xs uppercase tracking-wider flex items-center gap-2"
                    >
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px]" :class="step === 2 ? 'bg-hoyos-red text-white' : 'bg-hoyos-card text-hoyos-dim'">2</span>
                        Payment Method
                    </button>
                </div>

                <!-- STEP 1: Buyer & Delivery Information -->
                <div x-show="step === 1" class="space-y-6">
                    @auth
                        @if($savedAddresses->isNotEmpty())
                            <!-- Saved Addresses Selection -->
                            <div class="space-y-3 p-4 rounded-xl bg-hoyos-card/50 border border-hoyos-border">
                                <label class="text-xs font-bold uppercase tracking-wider text-white flex items-center justify-between">
                                    <span>Choose a Saved Address</span>
                                    <a href="{{ route('account.index', ['tab' => 'addresses']) }}" class="text-[11px] text-hoyos-red hover:underline font-semibold">+ Manage</a>
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    @foreach($savedAddresses as $addr)
                                        <div 
                                            @click="selectedAddressId = {{ $addr->id }}; fillAddress({{ json_encode($addr) }})"
                                            :class="selectedAddressId === {{ $addr->id }} ? 'border-hoyos-red bg-hoyos-darkred/30' : 'border-hoyos-border bg-hoyos-surface'"
                                            class="p-3 rounded-lg border cursor-pointer hover:border-hoyos-borderBright transition-all text-xs space-y-1"
                                        >
                                            <p class="font-bold text-white">{{ $addr->recipient_name }}</p>
                                            <p class="text-hoyos-muted">{{ $addr->recipient_phone }}</p>
                                            <p class="text-hoyos-dim truncate">{{ $addr->province }} - {{ $addr->address_line }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @else
                        <!-- Guest notice -->
                        <div class="p-3.5 rounded-xl bg-hoyos-card border border-hoyos-border flex items-center justify-between text-xs">
                            <span class="text-hoyos-muted">Checking out as <strong class="text-white">Guest</strong></span>
                            <a href="{{ route('login') }}" class="text-hoyos-red font-bold hover:underline">Sign In for 1-click address</a>
                        </div>
                    @endauth

                    <div class="space-y-4">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-white">
                            Buyer Information
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label for="customer_name" class="text-xs font-semibold text-hoyos-muted uppercase">Full Name *</label>
                                <input 
                                    type="text" 
                                    id="customer_name" 
                                    name="customer_name" 
                                    value="{{ old('customer_name', $user?->name ?? ($defaultAddress?->recipient_name ?? '')) }}"
                                    required 
                                    placeholder="Ex: Tanjiro"
                                    class="w-full px-4 py-2.5 rounded-lg bg-hoyos-card border border-hoyos-border text-white text-sm focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red"
                                />
                                @error('customer_name') <span class="text-xs text-hoyos-red">{{ $message }}</span> @enderror
                            </div>

                            <div class="space-y-1">
                                <label for="customer_phone" class="text-xs font-semibold text-hoyos-muted uppercase">Phone Number *</label>
                                <input 
                                    type="text" 
                                    id="customer_phone" 
                                    name="customer_phone" 
                                    value="{{ old('customer_phone', $user?->phone ?? ($defaultAddress?->recipient_phone ?? '')) }}"
                                    required 
                                    placeholder="Ex: 016696969"
                                    class="w-full px-4 py-2.5 rounded-lg bg-hoyos-card border border-hoyos-border text-white text-sm focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red"
                                />
                                @error('customer_phone') <span class="text-xs text-hoyos-red">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label for="customer_email" class="text-xs font-semibold text-hoyos-muted uppercase">Email Address (Optional)</label>
                            <input 
                                type="email" 
                                id="customer_email" 
                                name="customer_email" 
                                value="{{ old('customer_email', $user?->email) }}"
                                placeholder="For tracking confirmation updates"
                                class="w-full px-4 py-2.5 rounded-lg bg-hoyos-card border border-hoyos-border text-white text-sm focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red"
                            />
                            @error('customer_email') <span class="text-xs text-hoyos-red">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="space-y-4 pt-4 border-t border-hoyos-border">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-white">
                                Delivery Information
                            </h2>
                            <span class="text-[11px] text-hoyos-dim">Partner: <strong>J&T Express</strong></span>
                        </div>

                        <div class="space-y-1">
                            <label for="province" class="text-xs font-semibold text-hoyos-muted uppercase">Province / City *</label>
                            <select 
                                id="province" 
                                name="province" 
                                required
                                class="w-full px-4 py-2.5 rounded-lg bg-hoyos-card border border-hoyos-border text-white text-sm focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red"
                            >
                                @foreach($provinces as $prov)
                                    <option value="{{ $prov }}" {{ old('province', $defaultAddress?->province) === $prov ? 'selected' : '' }}>
                                        {{ $prov }}
                                    </option>
                                @endforeach
                            </select>
                            @error('province') <span class="text-xs text-hoyos-red">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <label for="shipping_address" class="text-xs font-semibold text-hoyos-muted uppercase">Street & Sangkat / House Number *</label>
                            <input 
                                type="text" 
                                id="shipping_address" 
                                name="shipping_address" 
                                value="{{ old('shipping_address', $defaultAddress?->address_line ?? '') }}"
                                required 
                                placeholder="Ex: Toul Tompoung, St 155, House 42"
                                class="w-full px-4 py-2.5 rounded-lg bg-hoyos-card border border-hoyos-border text-white text-sm focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red"
                            />
                            @error('shipping_address') <span class="text-xs text-hoyos-red">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <label for="delivery_notes" class="text-xs font-semibold text-hoyos-muted uppercase">Courier Instructions / Notes</label>
                            <textarea 
                                id="delivery_notes" 
                                name="delivery_notes" 
                                rows="2"
                                placeholder="Ex: Call 10 mins before arrival, or leave at gate"
                                class="w-full px-4 py-2.5 rounded-lg bg-hoyos-card border border-hoyos-border text-white text-sm focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red"
                            >{{ old('delivery_notes', $defaultAddress?->notes ?? '') }}</textarea>
                        </div>

                        @auth
                            <div class="pt-2">
                                <label class="flex items-center gap-2 cursor-pointer text-xs text-hoyos-muted">
                                    <input type="checkbox" name="save_address" value="1" class="rounded bg-hoyos-card border-hoyos-border text-hoyos-red focus:ring-hoyos-red">
                                    <span>Save this delivery address to my account for faster checkout</span>
                                </label>
                            </div>
                        @endauth
                    </div>

                    <button 
                        type="button" 
                        @click="step = 2"
                        class="w-full py-3.5 rounded-full bg-hoyos-red hover:bg-hoyos-redHover text-white text-xs font-bold uppercase tracking-wider transition-all shadow-crimson-subtle"
                    >
                        Continue to Payment →
                    </button>
                </div>

                <!-- STEP 2: Payment Method -->
                <div x-show="step === 2" class="space-y-6">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-white">
                        Select Payment Method
                    </h2>

                    <div class="space-y-3">
                        <!-- Option 1: ABA KHQR (From Figma) -->
                        <label 
                            class="flex items-center justify-between p-4 rounded-xl border cursor-pointer transition-all"
                            :class="paymentMethod === 'aba_khqr' ? 'bg-hoyos-darkred/40 border-hoyos-red shadow-crimson-subtle ring-1 ring-hoyos-red' : 'bg-hoyos-card border-hoyos-border hover:border-hoyos-borderBright'"
                        >
                            <div class="flex items-center gap-3">
                                <input 
                                    type="radio" 
                                    name="payment_method" 
                                    value="aba_khqr" 
                                    x-model="paymentMethod"
                                    class="text-hoyos-red focus:ring-hoyos-red bg-hoyos-bg border-hoyos-border"
                                />
                                <div class="w-10 h-7 rounded bg-red-700 font-black text-white text-[10px] flex items-center justify-center tracking-tighter">
                                    ABA
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white">ABA KHQR</p>
                                    <p class="text-[11px] text-hoyos-muted">Scan to pay with any Cambodian banking app (Bakong standard)</p>
                                </div>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded bg-hoyos-red/20 text-hoyos-red font-bold">Instant</span>
                        </label>

                        <!-- Option 2: Cash on Delivery (COD) -->
                        <label 
                            class="flex items-center justify-between p-4 rounded-xl border cursor-pointer transition-all"
                            :class="paymentMethod === 'cod' ? 'bg-hoyos-darkred/40 border-hoyos-red shadow-crimson-subtle ring-1 ring-hoyos-red' : 'bg-hoyos-card border-hoyos-border hover:border-hoyos-borderBright'"
                        >
                            <div class="flex items-center gap-3">
                                <input 
                                    type="radio" 
                                    name="payment_method" 
                                    value="cod" 
                                    x-model="paymentMethod"
                                    class="text-hoyos-red focus:ring-hoyos-red bg-hoyos-bg border-hoyos-border"
                                />
                                <div class="w-10 h-7 rounded bg-hoyos-surface border border-hoyos-border font-bold text-white text-[10px] flex items-center justify-center">
                                    💵
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white">Cash on Delivery (COD)</p>
                                    <p class="text-[11px] text-hoyos-muted">Pay to the deliver when your package arrives</p>
                                </div>
                            </div>
                            <span class="text-xs text-hoyos-dim">J&T</span>
                        </label>
                    </div>

                    <div class="flex items-center gap-4 pt-4">
                        <button 
                            type="button" 
                            @click="step = 1" 
                            class="py-3.5 px-6 rounded-full border border-hoyos-border text-hoyos-muted hover:text-white text-xs font-bold uppercase tracking-wider"
                        >
                            ← Back
                        </button>

                        <button 
                            type="submit" 
                            class="flex-1 py-3.5 px-6 rounded-full bg-hoyos-red hover:bg-hoyos-redHover text-white text-xs font-black uppercase tracking-wider transition-all shadow-crimson-glow hover:shadow-[0_0_25px_rgba(255,0,46,0.8)]"
                        >
                            <span x-show="paymentMethod === 'aba_khqr'">🔒 Purchase with ABA KHQR</span>
                            <span x-show="paymentMethod === 'cod'" x-cloak>📦 Confirm Order (COD via J&T)</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right: Order Summary Box (From Figma) -->
            <div class="lg:col-span-5 space-y-6">
                <div class="p-6 rounded-2xl bg-hoyos-card border border-hoyos-border shadow-2xl space-y-6">
                    <h3 class="text-sm font-bold uppercase tracking-widest text-white border-b border-hoyos-border pb-3">
                        Summary
                    </h3>

                    <!-- Item Thumbnails List -->
                    <div class="space-y-4 max-h-72 overflow-y-auto pr-1">
                        @foreach($cart->items->sortBy(fn($i) => \App\Models\ProductVariant::sizeOrderIndex($i->variant?->size)) as $cartItem)
                            @php
                                $img = $cartItem->variant->product->primaryImage ? asset($cartItem->variant->product->primaryImage->image_path) : asset('images/hoyos/placeholder-item.png');
                            @endphp
                            <div class="flex items-center gap-3">
                                <img src="{{ $img }}" alt="{{ $cartItem->variant->product->name }}" class="w-12 h-14 object-cover rounded-lg bg-black border border-hoyos-border">
                                <div class="flex-1">
                                    <h4 class="text-xs font-semibold text-white truncate">{{ $cartItem->variant->product->name }}</h4>
                                    <p class="text-[11px] text-hoyos-dim">Size: <strong class="text-hoyos-red">{{ $cartItem->variant->size }}</strong> × {{ $cartItem->quantity }}</p>
                                </div>
                                <span class="text-xs font-bold text-white">${{ number_format($cartItem->subtotal, 2) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Breakdown Numbers -->
                    <div class="space-y-2.5 pt-4 border-t border-hoyos-border text-xs">
                        <div class="flex items-center justify-between text-hoyos-muted">
                            <span>Sub Total</span>
                            <span class="font-bold text-white">${{ number_format($cart->subtotal, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-hoyos-muted">
                            <span>Service Fee (TAX incl.)</span>
                            <span class="font-bold text-white">$0.00</span>
                        </div>
                        <div class="flex items-center justify-between text-hoyos-muted">
                            <span>Delivery (J&T Express)</span>
                            <span class="font-bold text-emerald-400">FREE</span>
                        </div>
                        <div class="pt-3 border-t border-hoyos-border flex items-center justify-between">
                            <span class="text-sm font-bold uppercase tracking-wider text-white">Total</span>
                            <span class="text-2xl font-black text-white drop-shadow-[0_0_10px_rgba(255,0,46,0.5)]">
                                ${{ number_format($cart->subtotal, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-hoyos-layout>
