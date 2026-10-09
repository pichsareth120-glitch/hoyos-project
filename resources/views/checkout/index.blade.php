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
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-display text-white uppercase tracking-wider">
                Checkout
            </h1>
            <p class="text-xs text-white/50 mt-1 font-mono">
                Complete your details to secure your pieces. Shipped safely nationwide via J&T Express.
            </p>
        </div>

        @if(config('hoyos.demo_mode'))
            <div class="mb-8 p-4 rounded-2xl glass border border-amber-500/40 flex items-start sm:items-center gap-3.5 text-xs text-amber-200 shadow-glass-card">
                <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center shrink-0 font-bold text-sm">
                    ⚡
                </div>
                <div>
                    <strong class="font-bold text-white uppercase tracking-wider block font-mono">Demo Store Mode Active</strong>
                    <span class="text-white/70">Public demonstration with simulated payments only. No actual money is debited or requested. Cash on Delivery is disabled in demo mode.</span>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('checkout.store') }}" class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
            @csrf

            <!-- Left: 2-Step Form -->
            <div class="lg:col-span-7 space-y-6">
                <!-- 2-Step Progress Stepper -->
                <x-ui.stepper 
                    :steps="[
                        ['number' => 1, 'title' => 'Delivery Information', 'description' => 'Address & Contact'],
                        ['number' => 2, 'title' => 'Payment Method', 'description' => 'KHQR or COD'],
                    ]"
                />

                <!-- STEP 1: Buyer & Delivery Information -->
                <div x-show="step === 1" class="space-y-6" x-cloak>
                    @auth
                        @if($savedAddresses->isNotEmpty())
                            <!-- Saved Addresses Selection -->
                            <div class="space-y-3 p-5 rounded-2xl glass border border-white/10">
                                <label class="text-xs font-mono font-bold uppercase tracking-wider text-white flex items-center justify-between">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-hoyos-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        Choose a Saved Address
                                    </span>
                                    <a href="{{ route('account.index', ['tab' => 'addresses']) }}" class="text-[11px] font-mono text-hoyos-red hover:underline font-semibold">+ Manage</a>
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    @foreach($savedAddresses as $addr)
                                        <div 
                                            @click="selectedAddressId = {{ $addr->id }}; fillAddress({{ json_encode($addr) }})"
                                            :class="selectedAddressId === {{ $addr->id }} ? 'border-hoyos-red/60 bg-hoyos-red/[0.08] shadow-[0_0_20px_rgba(255,0,46,0.25)]' : 'border-white/10 bg-white/[0.03] hover:border-white/20'"
                                            class="p-4 rounded-xl border cursor-pointer transition-all duration-200 text-xs space-y-1 group select-none focus-ring"
                                        >
                                            <div class="flex items-center justify-between">
                                                <p class="font-bold text-white font-mono">{{ $addr->recipient_name }}</p>
                                                <div 
                                                    class="w-4 h-4 rounded-full border flex items-center justify-center transition-colors"
                                                    :class="selectedAddressId === {{ $addr->id }} ? 'border-hoyos-red bg-hoyos-red text-white' : 'border-white/20'"
                                                >
                                                    <span class="w-1.5 h-1.5 rounded-full bg-white" x-show="selectedAddressId === {{ $addr->id }}"></span>
                                                </div>
                                            </div>
                                            <p class="text-white/60 font-mono text-[11px]">{{ $addr->recipient_phone }}</p>
                                            <p class="text-white/40 truncate text-[11px]">{{ $addr->province }} - {{ $addr->address_line }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @else
                        <!-- Guest notice -->
                        <div class="p-4 rounded-2xl glass border border-white/10 flex items-center justify-between text-xs">
                            <span class="text-white/60">Checking out as <strong class="text-white font-mono">Guest</strong></span>
                            <a href="{{ route('login') }}" class="text-hoyos-red font-mono font-bold hover:underline">Sign In for 1-click address →</a>
                        </div>
                    @endauth

                    <!-- Buyer Contact Information -->
                    <div class="glass rounded-2xl p-6 space-y-5">
                        <h2 class="text-sm font-mono font-bold uppercase tracking-wider text-white border-b border-white/10 pb-3 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-hoyos-red"></span>
                            Buyer Information
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <x-ui.input 
                                id="customer_name" 
                                name="customer_name" 
                                label="Full Name *"
                                value="{{ old('customer_name', $user?->name ?? ($defaultAddress?->recipient_name ?? '')) }}"
                                required 
                                placeholder="Recipient name"
                                :error="$errors->first('customer_name')"
                            />

                            <x-ui.input 
                                id="customer_phone" 
                                name="customer_phone" 
                                label="Phone Number *"
                                value="{{ old('customer_phone', $user?->phone ?? ($defaultAddress?->recipient_phone ?? '')) }}"
                                required 
                                placeholder="012 345 678"
                                :error="$errors->first('customer_phone')"
                            />
                        </div>

                        <x-ui.input 
                            type="email" 
                            id="customer_email" 
                            name="customer_email" 
                            label="Email Address (Optional)"
                            value="{{ old('customer_email', $user?->email) }}"
                            placeholder="For tracking receipt updates"
                            :error="$errors->first('customer_email')"
                        />
                    </div>

                    <!-- Delivery Destination -->
                    <div class="glass rounded-2xl p-6 space-y-5">
                        <div class="flex items-center justify-between border-b border-white/10 pb-3">
                            <h2 class="text-sm font-mono font-bold uppercase tracking-wider text-white flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-hoyos-red"></span>
                                Delivery Information
                            </h2>
                            <span class="text-[11px] font-mono text-white/40">Logistics Partner: <strong class="text-white">J&T Express</strong></span>
                        </div>

                        <div class="space-y-1.5">
                            <label for="province" class="block text-xs font-mono font-bold uppercase tracking-wider text-white">Province / City *</label>
                            <select 
                                id="province" 
                                name="province" 
                                required
                                class="w-full glass-input px-4 py-2.5 text-xs text-white focus:outline-none"
                            >
                                @foreach($provinces as $prov)
                                    <option value="{{ $prov }}" class="bg-[#120407]" {{ old('province', $defaultAddress?->province) === $prov ? 'selected' : '' }}>
                                        {{ $prov }}
                                    </option>
                                @endforeach
                            </select>
                            @error('province') <span class="text-xs text-hoyos-red font-medium">{{ $message }}</span> @enderror
                        </div>

                        <x-ui.input 
                            id="shipping_address" 
                            name="shipping_address" 
                            label="Street & Sangkat / House Number *"
                            value="{{ old('shipping_address', $defaultAddress?->address_line ?? '') }}"
                            required 
                            placeholder="Street, House No., Sangkat, Khan"
                            :error="$errors->first('shipping_address')"
                        />

                        <div class="space-y-1.5">
                            <label for="delivery_notes" class="block text-xs font-mono font-bold uppercase tracking-wider text-white">Courier Instructions / Notes</label>
                            <textarea 
                                id="delivery_notes" 
                                name="delivery_notes" 
                                rows="2"
                                placeholder="e.g. Call before delivery, drop at front gate"
                                class="w-full glass-input px-4 py-2.5 text-xs text-white placeholder:text-white/30 focus:outline-none"
                            >{{ old('delivery_notes', $defaultAddress?->notes ?? '') }}</textarea>
                        </div>

                        @auth
                            <div class="pt-2">
                                <label class="flex items-center gap-2.5 cursor-pointer text-xs text-white/70">
                                    <input type="checkbox" name="save_address" value="1" class="rounded bg-white/10 border-white/20 text-hoyos-red focus:ring-hoyos-red">
                                    <span>Save this delivery address to my account for faster checkout</span>
                                </label>
                            </div>
                        @endauth
                    </div>

                    <div class="pt-2">
                        <x-ui.button 
                            type="button" 
                            @click="step = 2"
                            variant="default"
                            size="lg"
                            class="w-full"
                        >
                            <span>Continue to Payment</span>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </x-ui.button>
                    </div>
                </div>

                <!-- STEP 2: Payment Method -->
                <div x-show="step === 2" class="space-y-6" x-cloak>
                    <div class="glass rounded-2xl p-6 space-y-5">
                        <h2 class="text-sm font-mono font-bold uppercase tracking-wider text-white border-b border-white/10 pb-3 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-hoyos-red"></span>
                            Select Payment Method
                        </h2>

                        <div class="space-y-3">
                            <!-- Option 1: ABA KHQR -->
                            <label 
                                class="flex items-center justify-between p-4 sm:p-5 rounded-2xl border cursor-pointer transition-all duration-200 select-none group focus-ring"
                                :class="paymentMethod === 'aba_khqr' ? 'glass-selected' : 'glass hover:border-white/20'"
                            >
                                <div class="flex items-center gap-4">
                                    <input 
                                        type="radio" 
                                        name="payment_method" 
                                        value="aba_khqr" 
                                        x-model="paymentMethod"
                                        class="sr-only"
                                    />
                                    <!-- Radio indicator circle -->
                                    <div 
                                        class="w-5 h-5 rounded-full border flex items-center justify-center shrink-0 transition-colors"
                                        :class="paymentMethod === 'aba_khqr' ? 'border-hoyos-red bg-hoyos-red text-white shadow-[0_0_12px_rgba(255,0,46,0.6)]' : 'border-white/20'"
                                    >
                                        <span class="w-2 h-2 rounded-full bg-white" x-show="paymentMethod === 'aba_khqr'"></span>
                                    </div>

                                    <!-- KHQR Badge -->
                                    <div class="w-12 h-8 rounded-lg bg-[#E1251B] font-display text-white text-xs flex items-center justify-center tracking-tight shrink-0 shadow-sm">
                                        KHQR
                                    </div>

                                    <div>
                                        <p class="text-xs sm:text-sm font-mono font-bold text-white flex items-center gap-2">
                                            ABA / Bakong KHQR
                                            @if(config('hoyos.demo_mode'))
                                                <span class="text-[9px] px-2 py-0.5 rounded-full bg-amber-400/20 text-amber-300 font-mono font-semibold">SIMULATED DEMO</span>
                                            @endif
                                        </p>
                                        <p class="text-[11px] text-white/50 leading-relaxed">
                                            @if(config('hoyos.demo_mode'))
                                                Instant simulated QR approval — no real bank debit
                                            @else
                                                Scan to pay with any Cambodian banking app (Bakong standard)
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="shrink-0 pl-2">
                                    @if(config('hoyos.demo_mode'))
                                        <span class="text-[10px] font-mono px-2.5 py-1 rounded-full bg-amber-500/15 text-amber-300 font-bold border border-amber-500/30">Demo Mode</span>
                                    @else
                                        <span class="text-[10px] font-mono px-2.5 py-1 rounded-full bg-hoyos-red/20 text-hoyos-red font-bold border border-hoyos-red/40">Instant</span>
                                    @endif
                                </div>
                            </label>

                            <!-- Option 2: Cash on Delivery (COD) -->
                            @if(config('hoyos.demo_mode'))
                                <div class="flex items-center justify-between p-4 sm:p-5 rounded-2xl glass border border-white/5 opacity-50 cursor-not-allowed select-none">
                                    <div class="flex items-center gap-4">
                                        <div class="w-5 h-5 rounded-full border border-white/15 flex items-center justify-center shrink-0"></div>
                                        <div class="w-12 h-8 rounded-lg bg-white/[0.05] border border-white/10 font-bold text-white/40 text-xs flex items-center justify-center shrink-0">
                                            💵
                                        </div>
                                        <div>
                                            <p class="text-xs sm:text-sm font-mono font-bold text-white/50">Cash on Delivery (COD)</p>
                                            <p class="text-[11px] text-white/40">Disabled in Demo Mode — Please test via Simulated ABA KHQR</p>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-white/[0.05] text-white/40 border border-white/10">Disabled</span>
                                </div>
                            @else
                                <label 
                                    class="flex items-center justify-between p-4 sm:p-5 rounded-2xl border cursor-pointer transition-all duration-200 select-none group focus-ring"
                                    :class="paymentMethod === 'cod' ? 'glass-selected' : 'glass hover:border-white/20'"
                                >
                                    <div class="flex items-center gap-4">
                                        <input 
                                            type="radio" 
                                            name="payment_method" 
                                            value="cod" 
                                            x-model="paymentMethod"
                                            class="sr-only"
                                        />
                                        <div 
                                            class="w-5 h-5 rounded-full border flex items-center justify-center shrink-0 transition-colors"
                                            :class="paymentMethod === 'cod' ? 'border-hoyos-red bg-hoyos-red text-white shadow-[0_0_12px_rgba(255,0,46,0.6)]' : 'border-white/20'"
                                        >
                                            <span class="w-2 h-2 rounded-full bg-white" x-show="paymentMethod === 'cod'"></span>
                                        </div>

                                        <div class="w-12 h-8 rounded-lg bg-white/[0.08] border border-white/15 font-bold text-white text-xs flex items-center justify-center shrink-0">
                                            💵
                                        </div>
                                        <div>
                                            <p class="text-xs sm:text-sm font-mono font-bold text-white">Cash on Delivery (COD)</p>
                                            <p class="text-[11px] text-white/50">Pay in cash directly to the J&T courier on delivery</p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-mono text-white/40">J&T</span>
                                </label>
                            @endif
                        </div>
                    </div>

                    <!-- Actions Bar (Back & Purchase) -->
                    <div class="flex items-center gap-4 pt-2">
                        <x-ui.button 
                            type="button" 
                            @click="step = 1" 
                            variant="secondary"
                            size="lg"
                        >
                            ← Back
                        </x-ui.button>

                        <x-ui.button 
                            type="submit" 
                            variant="default"
                            size="lg"
                            class="flex-1"
                        >
                            <span x-show="paymentMethod === 'aba_khqr'">
                                @if(config('hoyos.demo_mode'))
                                    ⚡ Proceed to Simulated ABA KHQR
                                @else
                                    🔒 Purchase with ABA KHQR
                                @endif
                            </span>
                            <span x-show="paymentMethod === 'cod'" x-cloak>
                                📦 Confirm Order (COD via J&T)
                            </span>
                        </x-ui.button>
                    </div>
                </div>
            </div>

            <!-- Right: Sticky Order Summary Box -->
            <div class="lg:col-span-5 lg:sticky lg:top-28">
                <div class="glass-elevated border border-white/15 rounded-2xl p-6 shadow-glass-elevated space-y-6">
                    <div class="border-b border-white/10 pb-4 flex items-center justify-between">
                        <h3 class="text-sm font-mono font-bold uppercase tracking-wider text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-hoyos-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            Order Summary
                        </h3>
                        <span class="text-[11px] font-mono text-white/50">{{ $cart->total_quantity }} items</span>
                    </div>

                    <!-- Item Thumbnails List -->
                    <div class="space-y-3.5 max-h-72 overflow-y-auto pr-1">
                        @foreach($cart->items->sortBy(fn($i) => \App\Models\ProductVariant::sizeOrderIndex($i->variant?->size)) as $cartItem)
                            @php
                                $img = $cartItem->variant->product->primaryImage ? asset($cartItem->variant->product->primaryImage->image_path) : asset('images/hoyos/placeholder-item.png');
                            @endphp
                            <div class="flex items-center gap-3.5 p-2 rounded-xl glass border border-white/5">
                                <img src="{{ $img }}" alt="{{ $cartItem->variant->product->name }}" class="w-12 h-14 object-cover rounded-lg bg-black border border-white/10 shrink-0">
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-xs font-semibold text-white truncate">{{ $cartItem->variant->product->name }}</h4>
                                    <p class="text-[11px] font-mono text-white/50 mt-0.5">
                                        Size: <strong class="text-hoyos-red">{{ $cartItem->variant->size }}</strong> × {{ $cartItem->quantity }}
                                    </p>
                                </div>
                                <span class="text-xs font-mono font-bold text-white shrink-0">${{ number_format($cartItem->subtotal, 2) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Breakdown Numbers -->
                    <div class="space-y-2.5 pt-4 border-t border-white/10 text-xs font-mono">
                        <div class="flex items-center justify-between text-white/60">
                            <span>Subtotal</span>
                            <span class="font-bold text-white">${{ number_format($cart->subtotal, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-white/60">
                            <span>Service Fee (TAX incl.)</span>
                            <span class="font-bold text-white">$0.00</span>
                        </div>
                        <div class="flex items-center justify-between text-white/60">
                            <span>Delivery (J&T Express)</span>
                            <span class="font-bold text-emerald-400">FREE</span>
                        </div>
                        <div class="pt-3 border-t border-white/10 flex items-center justify-between">
                            <span class="text-sm font-bold uppercase tracking-wider text-white">Total</span>
                            <span class="text-2xl font-black text-white drop-shadow-[0_0_12px_rgba(255,0,46,0.6)]">
                                ${{ number_format($cart->subtotal, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-hoyos-layout>
