<x-hoyos-layout title="Pay with KHQR - Order #{{ $order->order_number }}">
    <div 
        x-data="{
            orderNumber: '{{ $order->order_number }}',
            token: '{{ $token }}',
            status: '{{ $payment->status }}',
            step: ('{{ $payment->status }}' === 'paid' || '{{ $payment->status }}' === 'completed') ? 3 : 2,
            remainingSeconds: Math.max(0, Math.floor((new Date('{{ $payment->expires_at->toIso8601String() }}').getTime() - new Date().getTime()) / 1000)),
            isExpired: false,
            pollTimer: null,
            timerInterval: null,
            isSimulating: false,
            
            init() {
                if (this.status === 'paid' || this.status === 'completed') {
                    this.status = 'paid';
                    this.step = 3;
                    return;
                }

                if (this.remainingSeconds <= 0 || this.status === 'expired') {
                    this.isExpired = true;
                    return;
                }
                
                // Countdown loop
                this.timerInterval = setInterval(() => {
                    if (this.remainingSeconds > 0) {
                        this.remainingSeconds--;
                    } else {
                        this.isExpired = true;
                        clearInterval(this.timerInterval);
                        clearInterval(this.pollTimer);
                    }
                }, 1000);

                // Polling loop every 4 seconds against API status endpoint
                this.pollTimer = setInterval(() => {
                    if (!this.isExpired && this.status === 'pending') {
                        this.checkStatus();
                    }
                }, 4000);
            },

            formatTime(seconds) {
                const m = Math.floor(seconds / 60);
                const s = seconds % 60;
                return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            },

            async checkStatus() {
                try {
                    const url = `/api/orders/${this.orderNumber}/payment-status${this.token ? '?token=' + this.token : ''}`;
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (res.ok) {
                        const data = await res.json();
                        if (data.is_paid) {
                            clearInterval(this.pollTimer);
                            clearInterval(this.timerInterval);
                            this.status = 'paid';
                            this.step = 3;
                            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Payment confirmed! Redirecting...', type: 'success' } }));
                            setTimeout(() => {
                                window.location.href = data.redirect_url || `/orders/${this.orderNumber}/success${this.token ? '?token=' + this.token : ''}`;
                            }, 2000);
                        } else if (data.is_expired) {
                            this.isExpired = true;
                            this.status = 'expired';
                            clearInterval(this.pollTimer);
                            clearInterval(this.timerInterval);
                        }
                    }
                } catch (e) {
                    console.error('Polling error', e);
                }
            },

            async simulateApproval() {
                if (this.isSimulating || this.status === 'paid' || this.isExpired) {
                    return;
                }
                this.isSimulating = true;
                try {
                    const res = await fetch(`/payment/${this.orderNumber}/simulate${this.token ? '?token=' + this.token : ''}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.status = 'paid';
                        this.step = 3;
                        window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Sandbox payment verified! Redirecting...', type: 'success' } }));
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 2000);
                    } else {
                        this.isSimulating = false;
                        if (data.error) {
                            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: data.error, type: 'error' } }));
                        }
                    }
                } catch (e) {
                    console.error(e);
                    this.isSimulating = false;
                }
            }
        }"
        class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10"
    >
        <!-- 3-Step Progress Stepper -->
        <x-ui.stepper 
            :interactive="false"
            :steps="[
                ['number' => 1, 'title' => 'Delivery Information', 'description' => 'Address & Contact'],
                ['number' => 2, 'title' => 'Payment Method', 'description' => 'Bakong KHQR Scan'],
                ['number' => 3, 'title' => 'Order Confirmation', 'description' => 'Receipt & Tracking'],
            ]"
        />

        <!-- Centered Header -->
        <div class="text-center mb-8" aria-live="polite">
            <div class="inline-block mb-3">
                <!-- Paid State Badge -->
                <span 
                    x-show="status === 'paid'" 
                    x-cloak
                    class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-mono font-bold uppercase tracking-wider bg-emerald-500/15 text-emerald-400 border border-emerald-500/40 shadow-[0_0_20px_rgba(16,185,129,0.3)]"
                >
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Payment Confirmed
                </span>

                <!-- Expired State Badge -->
                <span 
                    x-show="(isExpired || status === 'expired') && status !== 'paid'" 
                    x-cloak
                    class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-mono font-bold uppercase tracking-wider bg-red-500/15 text-red-400 border border-red-500/40 shadow-[0_0_15px_rgba(239,68,68,0.25)]"
                >
                    <span class="w-2 h-2 rounded-full bg-red-400"></span>
                    Payment Expired
                </span>

                <!-- Awaiting Payment State Badge -->
                <span 
                    x-show="status === 'pending' && !isExpired" 
                    class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-mono font-bold uppercase tracking-wider bg-hoyos-red/15 text-hoyos-red border border-hoyos-red/40 shadow-[0_0_16px_rgba(255,0,46,0.25)]"
                >
                    <span class="w-2 h-2 rounded-full bg-hoyos-red animate-ping"></span>
                    Awaiting ABA / Bakong KHQR Payment
                </span>
            </div>

            <h1 class="text-3xl sm:text-4xl font-display text-white uppercase tracking-wider">
                <span x-show="status !== 'paid'">Scan & Pay with KHQR</span>
                <span x-show="status === 'paid'" x-cloak>Payment Confirmed</span>
            </h1>
            <p class="text-xs text-white/60 mt-2 font-mono">
                Order <span class="text-white font-bold">#{{ $order->order_number }}</span> • Payable Amount: <span class="text-white font-black text-sm bg-white/[0.06] px-2.5 py-1 rounded-lg border border-white/10 ml-1">${{ number_format($order->total_amount, 2) }} USD</span>
            </p>
        </div>

        @if(config('hoyos.demo_mode'))
            <div class="mb-8 p-4 sm:p-5 rounded-2xl glass border border-amber-500/40 flex items-start sm:items-center gap-4 text-xs text-amber-200 shadow-glass">
                <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center shrink-0 font-bold text-base">
                    ⚡
                </div>
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <strong class="font-bold text-white uppercase tracking-wider font-mono">Demo Store — Simulated Payments Only</strong>
                        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-amber-400/20 text-amber-300 font-bold border border-amber-400/30">NO REAL CHARGES</span>
                    </div>
                    <p class="text-amber-200/80 mt-1 leading-relaxed">
                        This is a public demonstration. No funds are debited or requested from any actual bank account. Use the simulation panel on the right to test instant confirmation and order receipt generation.
                    </p>
                </div>
            </div>
        @endif

        @if($order->order_status === 'cancelled')
            <div class="mb-8 p-6 glass border border-red-500/40 rounded-2xl text-center">
                <div class="w-12 h-12 mx-auto rounded-full bg-red-500/20 text-red-400 border border-red-500/30 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h2 class="text-base font-bold text-white uppercase tracking-wider">Order Cancelled</h2>
                <p class="text-xs text-red-200/80 mt-1 max-w-md mx-auto">
                    This order was cancelled because payment was not completed within the grace period (or by administration). Reserved items have been restored to inventory.
                </p>
                <div class="mt-4">
                    <x-ui.button href="{{ route('home') }}" variant="secondary" size="sm">
                        Return to Shop
                    </x-ui.button>
                </div>
            </div>
        @endif

        <!-- Symmetrical 2-Column Responsive Layout (Mobile: KHQR first) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Left Column: Authentic KHQR Standee Card & Order Details -->
            <div class="lg:col-span-6 w-full max-w-[420px] mx-auto space-y-6 flex flex-col items-center">
                <!-- KHQR Standee Card with Glass Frame -->
                <div class="w-full glass-elevated rounded-3xl overflow-hidden border border-white/15 shadow-glass-elevated relative">
                    <!-- KHQR Crimson Glow Header -->
                    <div class="bg-gradient-to-r from-[#FF002E] to-[#B90022] px-6 py-4 flex items-center justify-between text-white shadow-[0_4px_20px_rgba(255,0,46,0.35)]">
                        <div class="flex items-center gap-2.5">
                            <span class="font-display text-2xl tracking-tight font-black">KHQR</span>
                            <span class="text-[10px] uppercase font-bold font-mono tracking-widest bg-black/25 px-2 py-0.5 rounded-full border border-white/20">
                                <span x-show="status === 'paid'" x-cloak>CONFIRMED</span>
                                <span x-show="status !== 'paid'">{{ config('hoyos.demo_mode') ? 'DEMO SIMULATION' : 'Bakong' }}</span>
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] font-mono font-bold block uppercase tracking-wider text-white/80">Payable (USD)</span>
                            <span class="text-base font-mono font-black">${{ number_format($order->total_amount, 2) }}</span>
                        </div>
                    </div>

                    <!-- Pending / Expired State Body (Scannable Pure White Inner Card) -->
                    <div x-show="status !== 'paid'" class="p-6 bg-white flex flex-col items-center justify-center">
                        <div class="text-center mb-3">
                            <p class="text-xs font-black text-gray-900 uppercase tracking-wider">
                                {{ $payment->payload['merchant_name'] ?? (config('hoyos.demo_mode') ? 'HOYOS DEMO STORE (NON-PAYABLE)' : 'HOYOS CLOTHING CO., LTD') }}
                            </p>
                            <p class="text-[10px] text-gray-500 font-mono mt-0.5">
                                Merchant ID: {{ $payment->payload['merchant_id'] ?? (config('hoyos.demo_mode') ? 'DEMO-STORE-SIMULATION' : 'ec479222') }}
                            </p>
                        </div>

                        <!-- Scannable Pure White QR Frame (Zero blur/opacity to ensure phone readability) -->
                        <div class="relative p-2.5 bg-white rounded-2xl border-2 border-gray-200 shadow-md flex items-center justify-center">
                            @if(config('hoyos.demo_mode'))
                                <!-- Non-payable Demo Placeholder -->
                                <div class="w-60 h-60 rounded-xl bg-gray-50 border-2 border-dashed border-amber-400 flex flex-col items-center justify-center p-4 text-center relative overflow-hidden select-none">
                                    <div class="absolute inset-x-0 -rotate-12 bg-amber-500 text-black font-black font-mono text-[9px] uppercase tracking-wider py-1 shadow-sm">
                                        NON-PAYABLE DEMO
                                    </div>
                                    <div class="w-12 h-12 rounded-xl bg-amber-500/15 text-amber-600 flex items-center justify-center mb-2 mt-2">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <p class="text-xs font-black text-gray-900 uppercase tracking-tight">Do Not Scan</p>
                                    <p class="text-[10px] text-gray-600 mt-1 leading-snug">
                                        Live banking QR scanning is disabled for this public demo. No real funds can be debited.
                                    </p>
                                    <div class="mt-3 text-[9px] text-amber-900 font-bold font-mono bg-amber-100 border border-amber-200 px-2.5 py-1 rounded-md">
                                        USE SIMULATION PANEL 👉
                                    </div>
                                </div>
                            @else
                                <template x-if="!isExpired && status === 'pending'">
                                    <img 
                                        src="{{ !empty($payment->payload['qr_image']) ? $payment->payload['qr_image'] : 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&margin=8&data=' . urlencode($payment->qr_string) }}" 
                                        alt="Bakong KHQR Code" 
                                        class="w-60 h-60 object-contain rounded-lg"
                                    />
                                </template>
                            @endif

                            <!-- Expired State Overlay -->
                            <div 
                                x-show="isExpired" 
                                x-cloak
                                class="absolute inset-0 bg-white flex flex-col items-center justify-center p-5 text-center rounded-2xl z-10"
                            >
                                <svg class="w-12 h-12 text-hoyos-red mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-xs font-bold text-gray-900 uppercase tracking-wider">QR Code Expired</p>
                                <p class="text-[11px] text-gray-600 mt-1 mb-4 leading-relaxed">Dynamic KHQRs expire after 5 minutes. You can regenerate a new QR code within the grace period.</p>
                                @if($order->order_status !== 'cancelled')
                                    <form action="{{ route('payment.retry', ['orderNumber' => $order->order_number, 'token' => $token]) }}" method="POST">
                                        @csrf
                                        <x-ui.button type="submit" variant="default" size="sm">
                                            Generate New QR
                                        </x-ui.button>
                                    </form>
                                @else
                                    <span class="text-xs font-bold text-red-600 uppercase font-mono">Order Cancelled</span>
                                @endif
                            </div>
                        </div>

                        <!-- Timer under QR -->
                        <div class="mt-4 flex items-center gap-2">
                            <span class="text-xs text-gray-500 font-medium">Expires in:</span>
                            <span 
                                class="font-mono text-sm font-bold"
                                :class="remainingSeconds < 60 ? 'text-hoyos-red animate-pulse' : 'text-gray-900'"
                                x-text="formatTime(remainingSeconds)"
                            ></span>
                        </div>
                    </div>

                    <!-- Clean Verified Success State (Dedicated Solid View, Zero Bleed-Through) -->
                    <div 
                        x-show="status === 'paid'" 
                        x-cloak
                        class="p-8 bg-white flex flex-col items-center justify-center text-center min-h-[380px]"
                    >
                        <!-- Animated Green Checkmark Badge -->
                        <div class="relative mb-5">
                            <div class="w-20 h-20 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center border-2 border-emerald-500/20 shadow-[0_0_25px_rgba(16,185,129,0.25)]">
                                <svg class="w-10 h-10 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="absolute -top-1 -right-1 flex h-4 w-4">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-emerald-500"></span>
                            </span>
                        </div>

                        <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-3 py-1 rounded-full mb-2">
                            Transaction Approved
                        </span>

                        <h2 class="text-2xl font-display text-gray-900 uppercase tracking-wider mb-1">
                            Payment Verified!
                        </h2>

                        <div class="my-4 py-2 px-5 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center gap-2">
                            <span class="text-xs text-gray-500 font-medium">Amount Paid:</span>
                            <span class="font-mono font-bold text-sm text-gray-900">${{ number_format($order->total_amount, 2) }} USD</span>
                        </div>

                        <div class="flex items-center gap-2 text-xs text-gray-500 mt-2">
                            <svg class="animate-spin w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span class="font-medium text-xs text-gray-600 font-mono">Redirecting to order receipt...</span>
                        </div>
                    </div>

                    <!-- Bottom instructions / Network Confirmation -->
                    <div class="bg-gray-50 border-t border-gray-100 p-3.5 text-center">
                        <p x-show="status === 'paid'" x-cloak class="text-xs text-emerald-700 font-semibold flex items-center justify-center gap-1.5 font-mono">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Authorized & Verified via Bakong KHQR Network
                        </p>
                        <p x-show="status !== 'paid'" class="text-[11px] text-gray-600 font-medium">
                            @if(config('hoyos.demo_mode'))
                                Simulated Demo Transaction • No live bank connection or charges.
                            @else
                                Scan with ABA Mobile, Bakong, Wing, ACLEDA, or any KHQR banking app.
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Mobile Deep Link (Hidden once paid or expired) -->
                @if(!config('hoyos.demo_mode'))
                    @php
                        $deeplink = $payment->payload['abapay_deeplink'] ?? $payment->payload['mobile_deeplink'] ?? null;
                    @endphp
                    @if(!empty($deeplink))
                        <div x-show="status === 'pending' && !isExpired" class="w-full text-center">
                            <a 
                                href="{{ $deeplink }}" 
                                class="inline-flex items-center justify-center gap-2 w-full py-3.5 px-4 glass glass-interactive border border-white/15 rounded-2xl text-xs font-bold text-white uppercase tracking-wider transition-all shadow-glass"
                            >
                                <svg class="w-4 h-4 text-hoyos-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                Open in ABA Mobile App
                            </a>
                            <p class="text-[10px] text-white/50 mt-1.5 font-mono">Mobile users: directly launches your banking app to authorize.</p>
                        </div>
                    @endif
                @endif

                <!-- Order Details Card -->
                <div class="w-full glass p-6 rounded-2xl border border-white/10 shadow-glass">
                    <div class="flex items-center justify-between pb-3.5 border-b border-white/10 mb-4">
                        <h4 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2 font-mono">
                            <svg class="w-4 h-4 text-hoyos-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            Order Details
                        </h4>
                        <span class="text-xs font-mono font-bold text-white/60">#{{ $order->order_number }}</span>
                    </div>
                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between py-1 border-b border-white/[0.06] text-white/60">
                            <span>Recipient</span>
                            <span class="text-white font-medium">{{ $order->customer_name }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-white/[0.06] text-white/60">
                            <span>Phone</span>
                            <span class="text-white font-mono">{{ $order->customer_phone }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-white/[0.06] text-white/60">
                            <span>Delivery Destination</span>
                            <span class="text-white text-right">{{ $order->province }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-white/[0.06] text-white/60">
                            <span>Items</span>
                            <span class="text-white font-medium">{{ $order->items->sum('quantity') }} items</span>
                        </div>
                        <div class="flex justify-between pt-1.5 text-white/70">
                            <span class="font-bold text-white uppercase tracking-wider font-mono">Total Payable</span>
                            <span class="text-white font-mono font-black text-sm text-hoyos-red">${{ number_format($order->total_amount, 2) }} USD</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: How to Pay Guide & Developer Simulation Panel -->
            <div class="lg:col-span-6 w-full max-w-[420px] mx-auto space-y-6">
                <!-- How to Pay Guide (Dims when payment confirmed) -->
                <div 
                    class="glass p-6 rounded-2xl border border-white/10 shadow-glass transition-opacity duration-300"
                    :class="status === 'paid' ? 'opacity-30 pointer-events-none' : ''"
                >
                    @if(config('hoyos.demo_mode'))
                        <h3 class="text-xs font-semibold text-white uppercase tracking-[0.04em] mb-4 flex items-center gap-2 font-mono">
                            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                            How Demo Simulation Works
                        </h3>
                        <ol class="space-y-3.5 text-xs text-white/70">
                            <li class="flex items-start gap-3">
                                <span class="w-6 h-6 rounded-full bg-white/[0.06] text-amber-400 border border-white/10 font-mono font-bold flex items-center justify-center shrink-0 text-[10px]">1</span>
                                <span class="leading-relaxed">This is a public demonstration with <strong>simulated payments only</strong>. Real banking QR scanning is disabled.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-6 h-6 rounded-full bg-white/[0.06] text-amber-400 border border-white/10 font-mono font-bold flex items-center justify-center shrink-0 text-[10px]">2</span>
                                <span class="leading-relaxed">Verify your order details and payable total (<strong class="text-white">${{ number_format($order->total_amount, 2) }} USD</strong>).</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-6 h-6 rounded-full bg-white/[0.06] text-amber-400 border border-white/10 font-mono font-bold flex items-center justify-center shrink-0 text-[10px]">3</span>
                                <span class="leading-relaxed">Click the <strong>⚡ Simulate Instant ABA Payment Approval</strong> button below.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-6 h-6 rounded-full bg-white/[0.06] text-amber-400 border border-white/10 font-mono font-bold flex items-center justify-center shrink-0 text-[10px]">4</span>
                                <span class="leading-relaxed">The order is verified and you are redirected to the <strong>order receipt and live tracking timeline</strong>.</span>
                            </li>
                        </ol>
                    @else
                        <h3 class="text-xs font-semibold text-white uppercase tracking-[0.04em] mb-4 flex items-center gap-2 font-mono">
                            <span class="w-2 h-2 rounded-full bg-hoyos-red"></span>
                            How to Pay
                        </h3>
                        <ol class="space-y-3.5 text-xs text-white/70">
                            <li class="flex items-start gap-3">
                                <span class="w-6 h-6 rounded-full bg-white/[0.06] text-hoyos-red border border-white/10 font-mono font-bold flex items-center justify-center shrink-0 text-[10px]">1</span>
                                <span class="leading-relaxed">Open your preferred Cambodian banking app (<strong class="text-white">ABA Mobile</strong>, <strong class="text-white">Bakong</strong>, <strong class="text-white">Wing Bank</strong>, <strong class="text-white">ACLEDA</strong>, etc.).</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-6 h-6 rounded-full bg-white/[0.06] text-hoyos-red border border-white/10 font-mono font-bold flex items-center justify-center shrink-0 text-[10px]">2</span>
                                <span class="leading-relaxed">Tap the <strong class="text-white">QR Pay / Scan</strong> icon in your banking app.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-6 h-6 rounded-full bg-white/[0.06] text-hoyos-red border border-white/10 font-mono font-bold flex items-center justify-center shrink-0 text-[10px]">3</span>
                                <span class="leading-relaxed">Scan the official <strong class="text-white">KHQR code</strong> on your screen and confirm the <strong class="text-white font-mono">${{ number_format($order->total_amount, 2) }} USD</strong> payment.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-6 h-6 rounded-full bg-white/[0.06] text-hoyos-red border border-white/10 font-mono font-bold flex items-center justify-center shrink-0 text-[10px]">4</span>
                                <span class="leading-relaxed">Your payment is verified automatically in real-time. Do not close this browser window.</span>
                            </li>
                        </ol>
                    @endif
                </div>

                <!-- Developer / Demo Simulation Panel (Glassmorphism Restyle) -->
                @if(config('hoyos.demo_mode') || (in_array(app()->environment(), ['local', 'testing']) && config('hoyos.allow_payment_simulation')))
                    <div class="glass p-6 rounded-2xl border {{ config('hoyos.demo_mode') ? 'border-amber-500/40 bg-amber-950/15' : 'border-white/15' }} relative overflow-hidden shadow-glass">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-mono uppercase tracking-wider font-bold {{ config('hoyos.demo_mode') ? 'text-amber-400' : 'text-hoyos-red' }} flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full {{ config('hoyos.demo_mode') ? 'bg-amber-400' : 'bg-hoyos-red' }} animate-ping"></span>
                                {{ config('hoyos.demo_mode') ? 'Demo Simulation Panel' : 'Developer Sandbox' }}
                            </span>
                            <span class="text-[10px] {{ config('hoyos.demo_mode') ? 'bg-amber-400/20 text-amber-300 border border-amber-400/30' : 'bg-hoyos-red/20 text-hoyos-red border border-hoyos-red/30' }} font-mono px-2.5 py-0.5 rounded-full font-bold">
                                {{ config('hoyos.demo_mode') ? 'DEMO MODE' : 'DEV MODE' }}
                            </span>
                        </div>
                        <p class="text-xs text-white/70 mb-5 leading-relaxed">
                            {{ config('hoyos.demo_mode') 
                                ? 'Simulate instant confirmation of this order without transferring any real funds:' 
                                : 'To test order fulfillment without live ABA credentials, simulate an instant ABA Bakong approval below:' }}
                        </p>
                        <button 
                            type="button" 
                            @click="simulateApproval()" 
                            :disabled="isSimulating || isExpired || status === 'paid'"
                            class="w-full py-3 px-4 {{ config('hoyos.demo_mode') ? 'bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-black font-black shadow-[0_0_20px_rgba(245,158,11,0.35)]' : 'bg-hoyos-red hover:bg-hoyos-red-hover text-white font-bold glow-primary' }} text-xs uppercase tracking-wider rounded-xl transition-all flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed font-mono"
                        >
                            <span x-show="!isSimulating && status !== 'paid'" class="flex items-center gap-1.5">
                                <span>⚡</span> Simulate Instant ABA Payment Approval
                            </span>
                            <span x-show="isSimulating && status !== 'paid'" class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4 {{ config('hoyos.demo_mode') ? 'text-black' : 'text-white' }}" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Verifying simulated approval...
                            </span>
                            <span x-show="status === 'paid'" x-cloak class="flex items-center gap-2 text-black">
                                <svg class="w-4 h-4 text-emerald-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                Payment Verified — Redirecting...
                            </span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-hoyos-layout>
