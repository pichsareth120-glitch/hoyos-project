<x-hoyos-layout title="Track Order #{{ $order->order_number }}">
    <div 
        x-data="{
            trackingCode: '{{ $shipment ? $shipment->tracking_number : '' }}',
            copied: false,
            copyTracking() {
                if (!this.trackingCode) return;
                navigator.clipboard.writeText(this.trackingCode);
                this.copied = true;
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Tracking code copied to clipboard!', type: 'success' } }));
                setTimeout(() => this.copied = false, 2500);
            }
        }"
        class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10"
    >
        <!-- Back Navigation & Title -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <a 
                    href="{{ route('orders.show', ['orderNumber' => $order->order_number, 'token' => $token]) }}"
                    class="text-xs font-mono font-bold text-white/60 hover:text-white uppercase tracking-wider inline-flex items-center gap-1.5 mb-2 transition-colors"
                >
                    ← Back to Order Receipt
                </a>
                <h1 class="text-3xl sm:text-4xl font-display text-white uppercase tracking-wider">
                    Shipment Tracking
                </h1>
                <p class="text-xs text-white/50 mt-1 font-mono">
                    Order <span class="text-white font-bold">#{{ $order->order_number }}</span> • Placed {{ $order->created_at->format('M d, Y') }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs font-mono uppercase text-white/50">Order Status:</span>
                <x-ui.badge :variant="$order->payment_status" size="sm">
                    {{ $order->order_status }}
                </x-ui.badge>
            </div>
        </div>

        <!-- Hero Carrier & Tracking Code Card -->
        <div class="glass-elevated border border-white/15 rounded-3xl p-6 sm:p-8 mb-8 shadow-glass-elevated relative overflow-hidden">
            <!-- Subtle atmospheric glow in corner -->
            <div class="absolute -top-20 -right-20 w-48 h-48 rounded-full bg-hoyos-red/10 blur-3xl pointer-events-none"></div>

            <div class="grid grid-cols-1 sm:grid-cols-12 gap-6 items-center">
                <!-- Carrier Info -->
                <div class="sm:col-span-5 border-b sm:border-b-0 sm:border-r border-white/10 pb-6 sm:pb-0 sm:pr-6 space-y-2">
                    <span class="text-[10px] font-mono uppercase tracking-widest text-white/40 font-bold block">
                        Official Logistics Partner
                    </span>
                    <div class="flex items-center gap-2.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-hoyos-red shadow-[0_0_8px_#FF002E]"></span>
                        <h2 class="text-xl font-mono font-bold text-white uppercase tracking-wider">
                            {{ $shipment && $shipment->carrier ? $shipment->carrier : 'J&T Express' }}
                        </h2>
                    </div>
                    <p class="text-xs text-white/50 leading-relaxed font-sans">
                        Nationwide doorstep delivery across all Cambodian provinces & districts.
                    </p>
                </div>

                <!-- Tracking Number & Actions -->
                <div class="sm:col-span-7 space-y-4">
                    <span class="text-[10px] font-mono uppercase tracking-widest text-white/40 font-bold block">
                        Waybill / Tracking Number
                    </span>

                    @if($shipment && $shipment->tracking_number)
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-xl sm:text-2xl font-mono font-black text-white tracking-widest glass px-4 py-2 rounded-xl border border-white/15 select-all shadow-inner">
                                {{ $shipment->tracking_number }}
                            </span>

                            <x-ui.button 
                                type="button" 
                                @click="copyTracking()" 
                                variant="secondary"
                                size="sm"
                            >
                                <svg class="w-4 h-4 text-hoyos-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span x-text="copied ? 'Copied!' : 'Copy Code'"></span>
                            </x-ui.button>
                        </div>

                        <!-- External J&T Portal Link Button -->
                        <div class="pt-1">
                            <a 
                                href="{{ $shipment->tracking_url ?: 'https://www.jtexpress.com.kh/tracking?billcode=' . $shipment->tracking_number }}" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl glass border border-hoyos-red/40 hover:border-hoyos-red text-xs font-mono font-bold text-white uppercase tracking-wider transition-all hover:shadow-crimson-subtle"
                            >
                                <span>Track on J&T Portal</span>
                                <svg class="w-3.5 h-3.5 text-hoyos-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                            <span class="text-[10px] font-mono text-white/40 ml-2">(Live GPS Telemetry)</span>
                        </div>
                    @else
                        <div class="glass rounded-xl p-4 border border-dashed border-white/15">
                            <p class="text-xs font-mono font-bold text-white uppercase tracking-wider">Awaiting Courier Dispatch</p>
                            <p class="text-[11px] text-white/50 mt-1 leading-relaxed">
                                Your order is currently being prepared. A J&T Express tracking code will appear here as soon as our fulfillment team packages your garments.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 5-Stage Hoyos Fulfillment Timeline (Vertical Glowing Timeline) -->
        <div class="glass rounded-3xl p-6 sm:p-8 mb-8 space-y-6">
            <div class="flex items-center justify-between pb-4 border-b border-white/10">
                <div>
                    <h3 class="text-sm font-mono font-bold text-white uppercase tracking-wider">
                        Fulfillment Progress
                    </h3>
                    <p class="text-xs text-white/50 mt-0.5">
                        Internal Hoyos fulfillment milestone tracker.
                    </p>
                </div>
                @if($shipment)
                    <x-ui.badge variant="confirmed" size="sm">
                        {{ $shipment->status_label }}
                    </x-ui.badge>
                @endif
            </div>

            @php
                $stages = [
                    'order_confirmed' => [
                        'label' => 'Order Confirmed',
                        'desc' => 'Order verified and queued for fulfillment.',
                    ],
                    'processing' => [
                        'label' => 'Processing',
                        'desc' => 'Garments carefully inspected and packed.',
                    ],
                    'shipped' => [
                        'label' => 'Shipped',
                        'desc' => 'Parcel handed over to J&T Express courier.',
                    ],
                    'out_for_delivery' => [
                        'label' => 'Out for Delivery',
                        'desc' => 'Courier is currently delivering in your destination area.',
                    ],
                    'delivered' => [
                        'label' => 'Delivered',
                        'desc' => 'Package successfully received by customer.',
                    ],
                ];

                $currentStatus = $shipment ? $shipment->status : 'order_confirmed';
                $stageKeys = array_keys($stages);
                $currentIndex = array_search($currentStatus, $stageKeys);
                if ($currentIndex === false) $currentIndex = 0;
            @endphp

            <!-- Timeline List -->
            <div class="relative pl-7 sm:pl-9 space-y-8 before:absolute before:left-3 sm:before:left-4 before:top-3 before:bottom-3 before:w-0.5 before:bg-white/10">
                @foreach($stages as $key => $stage)
                    @php
                        $index = array_search($key, $stageKeys);
                        $isCompleted = $index <= $currentIndex;
                        $isCurrent = $index === $currentIndex;
                    @endphp

                    <div class="relative flex items-start gap-4 sm:gap-6 group">
                        <!-- Node Circle Indicator -->
                        <div 
                            class="absolute -left-7 sm:-left-9 top-0.5 w-6 h-6 sm:w-8 sm:h-8 rounded-full flex items-center justify-center transition-all duration-300
                            {{ $isCurrent 
                                ? 'bg-hoyos-red text-white ring-4 ring-hoyos-red/20 shadow-crimson-glow' 
                                : ($isCompleted 
                                    ? 'bg-emerald-500 text-white shadow-[0_0_12px_rgba(34,197,94,0.35)]' 
                                    : 'glass border border-white/15 text-white/30') }}"
                        >
                            @if($isCompleted && !$isCurrent)
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            @elseif($isCurrent)
                                <span class="w-2.5 h-2.5 rounded-full bg-white animate-ping"></span>
                            @else
                                <span class="text-[10px] font-mono">{{ $index + 1 }}</span>
                            @endif
                        </div>

                        <!-- Stage Content -->
                        <div class="flex-1">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                <h4 class="text-xs sm:text-sm font-mono font-bold uppercase tracking-wider {{ $isCurrent ? 'text-white' : ($isCompleted ? 'text-white/90' : 'text-white/40') }}">
                                    {{ $stage['label'] }}
                                </h4>
                                @if($isCurrent && $shipment)
                                    <span class="text-[10px] font-mono text-hoyos-red font-bold uppercase tracking-wider">
                                        Current Milestone
                                    </span>
                                @elseif($isCompleted && $key === 'shipped' && $shipment && $shipment->shipped_at)
                                    <span class="text-[10px] font-mono text-white/50">
                                        {{ $shipment->shipped_at->format('M d, H:i') }}
                                    </span>
                                @elseif($isCompleted && $key === 'delivered' && $shipment && $shipment->delivered_at)
                                    <span class="text-[10px] font-mono text-emerald-400">
                                        {{ $shipment->delivered_at->format('M d, H:i') }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-white/60 mt-1 leading-relaxed font-sans">
                                {{ $stage['desc'] }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Disclaimer Notice -->
            <div class="pt-4 border-t border-white/10 flex items-start gap-2.5 text-[11px] text-white/50">
                <svg class="w-4 h-4 text-hoyos-red shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p>
                    <strong>Notice:</strong> Status milestones displayed above are maintained internally by Hoyos fulfillment personnel. Tracking codes are issued through J&T Express. For live GPS courier telemetry, tap "Track on J&T Portal".
                </p>
            </div>
        </div>

        <!-- Destination & Parcel Details Side by Side -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Destination Card -->
            <div class="glass rounded-2xl p-6 space-y-4">
                <h4 class="text-xs font-mono font-bold text-white uppercase tracking-wider pb-2 border-b border-white/10 flex items-center gap-2">
                    <svg class="w-4 h-4 text-hoyos-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Destination Information
                </h4>
                <div class="space-y-2.5 text-xs">
                    <div class="flex justify-between py-1 text-white/60 border-b border-white/5">
                        <span>Recipient</span>
                        <span class="text-white font-medium">{{ $order->customer_name }}</span>
                    </div>
                    <div class="flex justify-between py-1 text-white/60 border-b border-white/5">
                        <span>Phone</span>
                        <span class="text-white font-mono">{{ $order->customer_phone }}</span>
                    </div>
                    <div class="flex justify-between py-1 text-white/60 border-b border-white/5">
                        <span>Province / City</span>
                        <span class="text-white">{{ $order->province }}</span>
                    </div>
                    <div class="py-1 text-white/60">
                        <span class="block mb-1">Street Address:</span>
                        <span class="text-white font-medium">{{ $order->shipping_address }}</span>
                    </div>
                </div>
            </div>

            <!-- Package Contents Card -->
            <div class="glass rounded-2xl p-6 space-y-4">
                <h4 class="text-xs font-mono font-bold text-white uppercase tracking-wider pb-2 border-b border-white/10 flex items-center gap-2">
                    <svg class="w-4 h-4 text-hoyos-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    Package Contents ({{ $order->items->sum('quantity') }} items)
                </h4>
                <div class="space-y-3 max-h-52 overflow-y-auto pr-1">
                    @foreach($order->items->sortBy(fn($i) => \App\Models\ProductVariant::sizeOrderIndex($i->variant_size ?? $i->size)) as $item)
                        <div class="flex items-center gap-3 text-xs p-2 rounded-xl glass border border-white/5">
                            <span class="w-6 h-6 rounded-lg glass border border-white/15 flex items-center justify-center font-mono font-bold text-white text-[10px] shrink-0">
                                {{ $item->quantity }}x
                            </span>
                            <div class="flex-1 min-w-0">
                                <span class="text-white truncate block font-medium">{{ $item->product_title }}</span>
                                <span class="text-[10px] font-mono text-white/50">Size: {{ $item->size }}</span>
                            </div>
                            <span class="font-mono text-white shrink-0 font-bold">
                                ${{ number_format($item->total_price, 2) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-hoyos-layout>
