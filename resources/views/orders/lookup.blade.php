<x-hoyos-layout title="Track Your Order - Hoyos">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
        <!-- Page Header -->
        <div class="text-center mb-10">
            <span class="inline-block text-[11px] font-mono uppercase tracking-widest text-hoyos-red font-bold mb-2">
                Logistics & Delivery Lookup
            </span>
            <h1 class="text-3xl sm:text-5xl font-display text-white uppercase tracking-wider mb-3">
                Track Your Order
            </h1>
            <p class="text-xs sm:text-sm text-zinc-300 mt-2 max-w-lg mx-auto leading-relaxed">
                Enter your order number and the phone number provided at checkout to track fulfillment and view your J&T Express tracking code.
            </p>
        </div>

        <!-- Form Card Container with Atmospheric Radial Aura -->
        <div class="relative max-w-[520px] mx-auto">
            <!-- Atmospheric Depth Glow Behind Card -->
            <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-80 h-80 bg-[#FF002E]/[0.14] rounded-full blur-[100px] pointer-events-none -z-0"></div>

            <!-- Elevated Glass Form Card -->
            <div class="glass-elevated border border-white/15 rounded-3xl p-6 sm:p-9 shadow-glass-elevated relative z-10">
                <!-- Logged-in Customer Hint Banner -->
                @auth
                    <div class="mb-6 p-3.5 rounded-2xl glass-subtle border border-emerald-500/30 flex items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span class="text-white font-medium truncate">Logged in as {{ Auth::user()->name }}</span>
                        </div>
                        <a 
                            href="{{ route('account.index') }}" 
                            class="text-[11px] font-mono font-bold uppercase tracking-wider text-emerald-400 hover:text-emerald-300 flex items-center gap-1 shrink-0 transition-colors"
                        >
                            My Orders &rarr;
                        </a>
                    </div>
                @endauth

                <!-- Danger Glass Alert for Mismatch / Not Found -->
                @if(session('error'))
                    <div class="mb-6 p-4 rounded-2xl glass border border-red-500/50 bg-red-950/30 text-xs text-red-200 flex items-start gap-3 shadow-glass" role="alert">
                        <div class="w-6 h-6 rounded-lg bg-red-500/20 text-red-400 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <strong class="font-bold text-white block uppercase tracking-wide font-mono text-[11px]">Lookup Unsuccessful</strong>
                            <span class="text-red-200/90 leading-relaxed">{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                <form 
                    method="POST" 
                    action="{{ route('orders.lookup') }}" 
                    class="space-y-5"
                    x-data="{ submitting: false }"
                    @submit="submitting = true"
                >
                    @csrf

                    <!-- Order Number Input with Leading Icon -->
                    <x-ui.input 
                        label="Order Number"
                        id="order_number"
                        name="order_number"
                        type="text"
                        :required="true"
                        autocomplete="off"
                        value="{{ old('order_number') }}"
                        placeholder="e.g. HOYOS-000123"
                        helper="Found in your confirmation email or order receipt page"
                        :error="$errors->first('order_number')"
                        class="font-mono uppercase placeholder:font-mono placeholder:normal-case"
                        @input="$event.target.value = $event.target.value.toUpperCase()"
                    >
                        <x-slot:icon>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </x-slot:icon>
                    </x-ui.input>

                    <!-- Customer Phone Number Input with Leading Icon -->
                    <x-ui.input 
                        label="Customer Phone Number"
                        id="customer_phone"
                        name="customer_phone"
                        type="tel"
                        inputmode="tel"
                        :required="true"
                        value="{{ old('customer_phone') }}"
                        placeholder="e.g. 012345678"
                        helper="The phone number you entered during checkout"
                        :error="$errors->first('customer_phone')"
                        class="font-mono placeholder:font-mono"
                    >
                        <x-slot:icon>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </x-slot:icon>
                    </x-ui.input>

                    <!-- Submit Button with Loading State and Double-Submit Guard -->
                    <div class="pt-2">
                        <x-ui.button 
                            type="submit" 
                            variant="default" 
                            size="lg" 
                            class="w-full"
                            ::disabled="submitting"
                        >
                            <span x-show="!submitting" class="flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Locate Shipment
                            </span>
                            <span x-show="submitting" x-cloak class="flex items-center justify-center gap-2 font-mono">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Verifying Order Details...
                            </span>
                        </x-ui.button>
                    </div>
                </form>

                <!-- Guest Account Sign-in Link -->
                @guest
                    <div class="mt-6 pt-5 border-t border-white/10 text-center flex flex-col sm:flex-row items-center justify-center gap-2.5 text-xs text-white/60">
                        <span>Have a registered account?</span>
                        <a 
                            href="{{ route('login') }}" 
                            class="px-3.5 py-1.5 rounded-xl glass-subtle hover:glass text-white font-mono text-[11px] font-bold uppercase tracking-wider transition-all border border-white/10 hover:border-white/20 focus-ring"
                        >
                            Sign In &rarr;
                        </a>
                    </div>
                @endguest
            </div>

            <!-- Helpful Support Information Under Card -->
            <div class="mt-6 max-w-[520px] mx-auto p-4 sm:p-5 rounded-2xl glass border border-white/10 text-center space-y-2 shadow-glass text-xs">
                <p class="font-bold text-white uppercase tracking-wider font-mono flex items-center justify-center gap-1.5">
                    <svg class="w-4 h-4 text-hoyos-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Need Assistance Locating Your Order?
                </p>
                <p class="text-white/60 leading-relaxed text-[11px]">
                    Official J&T Express waybill tracking numbers appear automatically on your tracking timeline once our fulfillment team packages and dispatches your pieces.
                </p>
                <div class="pt-2 flex flex-wrap items-center justify-center gap-3 text-[11px] font-mono text-white/70">
                    <a href="mailto:Support@hoyos.com" class="hover:text-white transition-colors underline decoration-white/30">Support@hoyos.com</a>
                    <span class="text-white/30">•</span>
                    <span>+(855) 6969 6969</span>
                </div>
            </div>
        </div>
    </div>
</x-hoyos-layout>
