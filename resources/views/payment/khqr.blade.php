<x-hoyos-layout title="Pay with KHQR - Order #{{ $order->order_number }}">
    <div 
        x-data="{
            orderNumber: '{{ $order->order_number }}',
            token: '{{ $token }}',
            status: '{{ $payment->status }}',
            remainingSeconds: Math.max(0, Math.floor((new Date('{{ $payment->expires_at->toIso8601String() }}').getTime() - new Date().getTime()) / 1000)),
            isExpired: false,
            pollTimer: null,
            timerInterval: null,
            isSimulating: false,
            
            init() {
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

                // Polling loop every 3 seconds
                this.pollTimer = setInterval(() => {
                    if (!this.isExpired && this.status === 'pending') {
                        this.checkStatus();
                    }
                }, 3000);
            },

            formatTime(seconds) {
                const m = Math.floor(seconds / 60);
                const s = seconds % 60;
                return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            },

            async checkStatus() {
                try {
                    const url = `/payment/${this.orderNumber}/status${this.token ? '?token=' + this.token : ''}`;
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (res.ok) {
                        const data = await res.json();
                        if (data.is_paid) {
                            clearInterval(this.pollTimer);
                            clearInterval(this.timerInterval);
                            this.status = 'paid';
                            window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Payment confirmed! Redirecting...', type: 'success' } }));
                            setTimeout(() => {
                                window.location.href = data.redirect_url;
                            }, 1200);
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
                        window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Sandbox payment verified! Redirecting...', type: 'success' } }));
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 1000);
                    }
                } catch (e) {
                    console.error(e);
                    this.isSimulating = false;
                }
            }
        }"
        class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12"
    >
        <!-- Header -->
        <div class="text-center mb-8">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-mono font-bold uppercase tracking-wider bg-hoyos-red/10 text-hoyos-red border border-hoyos-red/30 mb-3">
                <span class="w-2 h-2 rounded-full bg-hoyos-red animate-pulse"></span>
                Awaiting ABA / Bakong KHQR Payment
            </span>
            <h1 class="text-3xl sm:text-4xl font-display text-white uppercase tracking-wider">
                Scan & Pay with KHQR
            </h1>
            <p class="text-xs text-hoyos-muted mt-2">
                Order <span class="text-white font-mono font-bold">#{{ $order->order_number }}</span> • Payable Amount: <span class="text-white font-bold font-mono text-sm">${{ number_format($order->total_amount, 2) }} USD</span>
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
            <!-- Left: Authentic KHQR Card -->
            <div class="md:col-span-7 flex flex-col items-center">
                <div class="w-full max-w-[340px] bg-white rounded-2xl overflow-hidden shadow-2xl border border-white/20 relative">
                    <!-- KHQR Official Red Banner -->
                    <div class="bg-[#E1251B] px-5 py-3.5 flex items-center justify-between text-white">
                        <div class="flex items-center gap-2">
                            <span class="font-display text-xl tracking-tight">KHQR</span>
                            <span class="text-[10px] uppercase font-bold tracking-widest bg-white/20 px-1.5 py-0.5 rounded">Bakong</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[11px] font-bold block uppercase tracking-wider opacity-90">USD</span>
                            <span class="text-xs font-mono font-black">${{ number_format($order->total_amount, 2) }}</span>
                        </div>
                    </div>

                    <!-- QR Body -->
                    <div class="p-6 bg-white flex flex-col items-center justify-center">
                        <div class="text-center mb-3">
                            <p class="text-xs font-black text-gray-900 uppercase tracking-wider">HOYOS CLOTHING CO., LTD</p>
                            <p class="text-[10px] text-gray-500 font-mono">ABA / Bakong Merchant ID: 000129384</p>
                        </div>

                        <!-- QR Code Frame -->
                        <div class="relative p-2 bg-white rounded-xl border-2 border-gray-200 shadow-sm flex items-center justify-center">
                            <template x-if="!isExpired && status === 'pending'">
                                <img 
                                    src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&margin=8&data={{ urlencode($payment->qr_string) }}" 
                                    alt="Bakong KHQR Code" 
                                    class="w-56 h-56 object-contain rounded"
                                />
                            </template>

                            <!-- Expired State Overlay -->
                            <div 
                                x-show="isExpired" 
                                x-cloak
                                class="absolute inset-0 bg-white/95 backdrop-blur-sm flex flex-col items-center justify-center p-4 text-center rounded-xl"
                            >
                                <svg class="w-12 h-12 text-hoyos-red mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-xs font-bold text-gray-900 uppercase">QR Code Expired</p>
                                <p class="text-[10px] text-gray-600 mt-1 mb-3">For your security, dynamic KHQRs expire after 5 minutes.</p>
                                <button 
                                    type="button" 
                                    @click="window.location.reload()" 
                                    class="px-4 py-2 bg-gray-900 text-white text-[11px] font-bold rounded-lg uppercase tracking-wider hover:bg-black"
                                >
                                    Refresh QR Code
                                </button>
                            </div>

                            <!-- Paid Overlay -->
                            <div 
                                x-show="status === 'paid'" 
                                x-cloak
                                class="absolute inset-0 bg-white/95 backdrop-blur-sm flex flex-col items-center justify-center p-4 text-center rounded-xl"
                            >
                                <div class="w-12 h-12 rounded-full bg-emerald-500/20 text-emerald-600 flex items-center justify-center mb-2">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <p class="text-xs font-black text-emerald-700 uppercase">Payment Verified!</p>
                                <p class="text-[10px] text-gray-600 mt-1">Redirecting to order receipt...</p>
                            </div>
                        </div>

                        <!-- Timer under QR -->
                        <div class="mt-4 flex items-center gap-2">
                            <span class="text-[11px] text-gray-500">Expires in:</span>
                            <span 
                                class="font-mono text-sm font-bold"
                                :class="remainingSeconds < 60 ? 'text-hoyos-red animate-pulse' : 'text-gray-900'"
                                x-text="formatTime(remainingSeconds)"
                            ></span>
                        </div>
                    </div>

                    <!-- Bottom instructions -->
                    <div class="bg-gray-50 border-t border-gray-100 p-3 text-center">
                        <p class="text-[10px] text-gray-500 font-medium">
                            Scan with ABA Mobile, Bakong, Wing, ACLEDA, or any KHQR banking app.
                        </p>
                    </div>
                </div>

                <!-- Mobile Deep Link (for users shopping directly on their mobile phone) -->
                @if(!empty($payment->payload['mobile_deeplink']))
                    <div class="mt-4 w-full max-w-[340px] text-center">
                        <a 
                            href="{{ $payment->payload['mobile_deeplink'] }}" 
                            class="inline-flex items-center justify-center gap-2 w-full py-3 px-4 bg-hoyos-card hover:bg-hoyos-hover border border-hoyos-border rounded-xl text-xs font-bold text-white uppercase tracking-wider transition-colors"
                        >
                            <svg class="w-4 h-4 text-hoyos-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            Open in ABA Mobile App
                        </a>
                        <p class="text-[10px] text-hoyos-dim mt-1.5">For mobile users: directly launches your banking app to authorize.</p>
                    </div>
                @endif
            </div>

            <!-- Right: Instructions & Dev Simulation Sandbox -->
            <div class="md:col-span-5 space-y-6">
                <!-- How to Pay Guide -->
                <div class="bg-hoyos-surface border border-hoyos-border rounded-2xl p-6">
                    <h3 class="text-sm font-semibold text-white uppercase tracking-[0.04em] mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-hoyos-red"></span>
                        How to Pay
                    </h3>
                    <ol class="space-y-3.5 text-xs text-hoyos-muted">
                        <li class="flex items-start gap-3">
                            <span class="w-5 h-5 rounded-full bg-hoyos-card text-hoyos-red border border-hoyos-border font-mono font-bold flex items-center justify-center shrink-0 text-[10px]">1</span>
                            <span>Open your preferred Cambodian banking app (<strong>ABA Mobile</strong>, <strong>Bakong</strong>, <strong>Wing Bank</strong>, <strong>ACLEDA</strong>, etc.).</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-5 h-5 rounded-full bg-hoyos-card text-hoyos-red border border-hoyos-border font-mono font-bold flex items-center justify-center shrink-0 text-[10px]">2</span>
                            <span>Tap the <strong>QR Pay / Scan</strong> icon in your banking app.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-5 h-5 rounded-full bg-hoyos-card text-hoyos-red border border-hoyos-border font-mono font-bold flex items-center justify-center shrink-0 text-[10px]">3</span>
                            <span>Scan the official <strong>KHQR code</strong> on your screen and confirm the <strong>${{ number_format($order->total_amount, 2) }} USD</strong> payment.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-5 h-5 rounded-full bg-hoyos-card text-hoyos-red border border-hoyos-border font-mono font-bold flex items-center justify-center shrink-0 text-[10px]">4</span>
                            <span>Your payment is verified automatically in real-time. Do not close this browser window.</span>
                        </li>
                    </ol>
                </div>

                <!-- Order Details Card -->
                <div class="bg-hoyos-surface border border-hoyos-border rounded-2xl p-6">
                    <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-3">Order Details</h4>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1 border-b border-hoyos-border/50 text-hoyos-muted">
                            <span>Recipient</span>
                            <span class="text-white font-medium">{{ $order->customer_name }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-hoyos-border/50 text-hoyos-muted">
                            <span>Phone</span>
                            <span class="text-white font-mono">{{ $order->customer_phone }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-hoyos-border/50 text-hoyos-muted">
                            <span>Delivery Destination</span>
                            <span class="text-white text-right">{{ $order->province }}</span>
                        </div>
                        <div class="flex justify-between py-1 text-hoyos-muted">
                            <span>Items</span>
                            <span class="text-white">{{ $order->items->sum('quantity') }} items</span>
                        </div>
                    </div>
                </div>

                <!-- Developer / Sandbox Simulation Widget -->
                <div class="bg-hoyos-card border-2 border-dashed border-hoyos-red/40 rounded-2xl p-5 relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-mono uppercase tracking-wider font-bold text-hoyos-red flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-hoyos-red animate-ping"></span>
                            Developer Sandbox Test Bar
                        </span>
                        <span class="text-[9px] bg-hoyos-red/20 text-hoyos-red font-mono px-2 py-0.5 rounded">DEV MODE</span>
                    </div>
                    <p class="text-[11px] text-hoyos-muted mb-4 leading-relaxed">
                        To test order fulfillment without live ABA credentials, simulate an instant ABA Bakong approval below:
                    </p>
                    <button 
                        type="button" 
                        @click="simulateApproval()" 
                        :disabled="isSimulating || isExpired"
                        class="w-full py-2.5 px-4 bg-hoyos-red hover:bg-hoyos-red-hover text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-glow flex items-center justify-center gap-2 disabled:opacity-50"
                    >
                        <span x-show="!isSimulating">⚡ Simulate Instant ABA Payment Approval</span>
                        <span x-show="isSimulating" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Verifying with ABA Bakong...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-hoyos-layout>
