<x-hoyos-layout title="Track Your Order - Hoyos">
    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center mb-8">
            <span class="inline-block text-[11px] font-mono uppercase tracking-widest text-hoyos-red font-bold mb-2">
                Logistics & Delivery Lookup
            </span>
            <h1 class="text-3xl sm:text-4xl font-display text-white uppercase tracking-wider">
                Track Your Order
            </h1>
            <p class="text-xs text-hoyos-muted mt-2">
                Enter your order number and the phone number provided at checkout to track fulfillment and view your J&T Express tracking code.
            </p>
        </div>

        <div class="bg-hoyos-surface border border-hoyos-border rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
            @if(session('error'))
                <div class="mb-6 p-4 rounded-xl bg-hoyos-red/10 border border-hoyos-red/30 text-xs text-hoyos-red flex items-start gap-2.5">
                    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('orders.lookup') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-white uppercase tracking-wider mb-2">
                        Order Number <span class="text-hoyos-red">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="order_number" 
                        value="{{ old('order_number') }}"
                        required 
                        placeholder="e.g. HOYOS-000123"
                        class="w-full bg-hoyos-card border border-hoyos-border focus:border-hoyos-red rounded-xl px-4 py-3 text-xs text-white placeholder-hoyos-dim font-mono focus:outline-none transition-colors"
                    />
                    @error('order_number')
                        <p class="text-[11px] text-hoyos-red mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-white uppercase tracking-wider mb-2">
                        Customer Phone Number <span class="text-hoyos-red">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="customer_phone" 
                        value="{{ old('customer_phone') }}"
                        required 
                        placeholder="e.g. 012345678"
                        class="w-full bg-hoyos-card border border-hoyos-border focus:border-hoyos-red rounded-xl px-4 py-3 text-xs text-white placeholder-hoyos-dim font-mono focus:outline-none transition-colors"
                    />
                    @error('customer_phone')
                        <p class="text-[11px] text-hoyos-red mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button 
                    type="submit" 
                    class="w-full py-3.5 bg-hoyos-red hover:bg-hoyos-red-hover text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-glow flex items-center justify-center gap-2 mt-4"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Locate Shipment
                </button>
            </form>

            <div class="mt-6 pt-5 border-t border-hoyos-border/50 text-center">
                <p class="text-[11px] text-hoyos-muted">
                    Have an account? 
                    <a href="{{ route('login') }}" class="text-white hover:text-hoyos-red font-bold underline transition-colors">
                        Sign in
                    </a> 
                    to view your full order history anytime.
                </p>
            </div>
        </div>
    </div>
</x-hoyos-layout>
