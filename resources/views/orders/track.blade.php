<x-hoyos-layout title="Track Order #{{ $order->order_number }}">
    <div 
        x-data="{
            trackingCode: '{{ $shipment ? $shipment->tracking_number : '' }}',
            copied: false,
            copyTracking() {
                if (!this.trackingCode) return;
                navigator.clipboard.writeText(this.trackingCode);
                this.copied = true;
                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Tracking number copied to clipboard', type: 'success' } }));
                setTimeout(() => this.copied = false, 2500);
            }
        }"
        class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12"
    >
        <!-- Back Navigation & Title -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <a 
                    href="{{ route('orders.show', ['orderNumber' => $order->order_number, 'token' => $token]) }}"
                    class="text-xs text-hoyos-muted hover:text-white uppercase font-bold tracking-wider inline-flex items-center gap-1.5 mb-2 transition-colors"
                >
                    ← Back to Order Receipt
                </a>
                <h1 class="text-3xl font-display text-white uppercase tracking-wider">
                    Shipment Tracking
                </h1>
                <p class="text-xs text-hoyos-muted mt-1">
                    Order <span class="font-mono text-white font-bold">#{{ $order->order_number }}</span> • Placed {{ $order->created_at->format('M d, Y') }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs text-hoyos-dim uppercase tracking-wider">Order Status:</span>
                <span class="px-3 py-1 rounded-full text-xs font-mono font-bold uppercase tracking-wider bg-hoyos-card border border-hoyos-border text-white">
                    {{ $order->order_status }}
                </span>
            </div>
        </div>

        <!-- Carrier & Tracking Code Card (matching Figma) -->
        <div class="bg-hoyos-surface border border-hoyos-border rounded-3xl p-6 sm:p-8 mb-8 shadow-2xl relative overflow-hidden">
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-6 items-center">
                <!-- Carrier Info -->
                <div class="sm:col-span-4 border-b sm:border-b-0 sm:border-r border-hoyos-border pb-6 sm:pb-0 sm:pr-6">
                    <span class="text-[10px] font-mono uppercase tracking-widest text-hoyos-dim font-bold block mb-1">
                        Delivery Carrier
                    </span>
                    <div class="flex items-center gap-2.5">
                        <span class="w-3 h-3 rounded-full bg-hoyos-red"></span>
                        <h2 class="text-xl font-semibold text-white uppercase tracking-[0.04em]">
                            {{ $shipment && $shipment->carrier ? $shipment->carrier : 'J&T Express' }}
                        </h2>
                    </div>
                    <p class="text-[11px] text-hoyos-muted mt-1">
                        Official logistics partner for nationwide Cambodia delivery.
                    </p>
                </div>

                <!-- Tracking Number & Actions -->
                <div class="sm:col-span-8 space-y-3">
                    <span class="text-[10px] font-mono uppercase tracking-widest text-hoyos-dim font-bold block">
                        Tracking Number
                    </span>

                    @if($shipment && $shipment->tracking_number)
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-2xl font-mono font-black text-white tracking-widest bg-hoyos-card px-4 py-2 rounded-xl border border-hoyos-border selection:bg-hoyos-red">
                                {{ $shipment->tracking_number }}
                            </span>

                            <button 
                                type="button" 
                                @click="copyTracking()" 
                                class="px-4 py-2.5 bg-hoyos-card hover:bg-hoyos-hover border border-hoyos-border text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all flex items-center gap-2"
                            >
                                <svg class="w-4 h-4 text-hoyos-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span x-text="copied ? 'Copied!' : 'Copy Tracking Number'"></span>
                            </button>
                        </div>

                        <!-- External J&T Portal Link Button -->
                        <div class="pt-2">
                            <a 
                                href="{{ $shipment->tracking_url ?: 'https://www.jtexpress.com.kh/tracking?billcode=' . $shipment->tracking_number }}" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 text-xs font-bold text-hoyos-red hover:text-white uppercase tracking-wider transition-colors"
                            >
                                <span>Track on Official J&T Portal</span>
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                            <span class="text-[10px] text-hoyos-dim ml-2">(Opens external J&T Cambodia website)</span>
                        </div>
                    @else
                        <div class="bg-hoyos-card/50 border border-dashed border-hoyos-border rounded-xl p-4">
                            <p class="text-xs font-bold text-white uppercase tracking-wider">Awaiting Courier Dispatch</p>
                            <p class="text-[11px] text-hoyos-muted mt-1">
                                Your order is currently being prepared. A J&T Express tracking code will appear here as soon as our fulfillment team packages your item.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 5-Stage Hoyos Internal Status Stepper -->
        <div class="bg-hoyos-surface border border-hoyos-border rounded-3xl p-6 sm:p-8 mb-8">
            <div class="flex items-center justify-between pb-6 mb-8 border-b border-hoyos-border">
                <div>
                    <h3 class="text-sm font-semibold text-white uppercase tracking-[0.04em]">
                        Fulfillment Progress
                    </h3>
                    <p class="text-[11px] text-hoyos-muted mt-0.5">
                        Internal Hoyos fulfillment status milestones.
                    </p>
                </div>
                @if($shipment)
                    <span class="text-xs font-mono font-bold uppercase tracking-wider text-hoyos-red bg-hoyos-red/10 px-3 py-1 rounded-full border border-hoyos-red/30">
                        {{ $shipment->status_label }}
                    </span>
                @endif
            </div>

            @php
                $stages = [
                    'order_confirmed' => [
                        'label' => 'Order Confirmed',
                        'desc' => 'Order verified and queued for fulfillment.',
                        'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                    ],
                    'processing' => [
                        'label' => 'Processing',
                        'desc' => 'Garments carefully inspected and packed.',
                        'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                    ],
                    'shipped' => [
                        'label' => 'Shipped',
                        'desc' => 'Parcel handed over to J&T Express courier.',
                        'icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
                    ],
                    'out_for_delivery' => [
                        'label' => 'Out for Delivery',
                        'desc' => 'Courier is currently delivering in your destination area.',
                        'icon' => 'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0',
                    ],
                    'delivered' => [
                        'label' => 'Delivered',
                        'desc' => 'Package successfully received by customer.',
                        'icon' => 'M5 13l4 4L19 7',
                    ],
                ];

                $currentStatus = $shipment ? $shipment->status : 'order_confirmed';
                $stageKeys = array_keys($stages);
                $currentIndex = array_search($currentStatus, $stageKeys);
                if ($currentIndex === false) $currentIndex = 0;
            @endphp

            <!-- Stepper Timeline (Vertical for clarity across all screens) -->
            <div class="relative pl-6 sm:pl-8 space-y-8 before:absolute before:left-3 sm:before:left-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-hoyos-border">
                @foreach($stages as $key => $stage)
                    @php
                        $index = array_search($key, $stageKeys);
                        $isCompleted = $index <= $currentIndex;
                        $isCurrent = $index === $currentIndex;
                    @endphp

                    <div class="relative flex items-start gap-4 sm:gap-6 group">
                        <!-- Node Circle -->
                        <div 
                            class="absolute -left-6 sm:-left-8 top-0.5 w-6 h-6 sm:w-7 sm:h-7 rounded-full flex items-center justify-center transition-all duration-300
                            {{ $isCompleted 
                                ? ($isCurrent ? 'bg-hoyos-red text-white ring-4 ring-hoyos-red/20 shadow-glow' : 'bg-emerald-500 text-white') 
                                : 'bg-hoyos-card border border-hoyos-border text-hoyos-dim' }}"
                        >
                            @if($isCompleted && !$isCurrent)
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            @elseif($isCurrent)
                                <span class="w-2 h-2 rounded-full bg-white animate-ping"></span>
                            @else
                                <span class="text-[10px] font-mono">{{ $index + 1 }}</span>
                            @endif
                        </div>

                        <!-- Stage Details -->
                        <div class="flex-1">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                <h4 class="text-xs sm:text-sm font-semibold uppercase tracking-[0.04em] {{ $isCurrent ? 'text-white' : ($isCompleted ? 'text-gray-200' : 'text-hoyos-dim') }}">
                                    {{ $stage['label'] }}
                                </h4>
                                @if($isCurrent && $shipment)
                                    <span class="text-[10px] font-mono text-hoyos-red font-bold uppercase">
                                        Current Status
                                    </span>
                                @elseif($isCompleted && $key === 'shipped' && $shipment && $shipment->shipped_at)
                                    <span class="text-[10px] font-mono text-hoyos-muted">
                                        {{ $shipment->shipped_at->format('M d, H:i') }}
                                    </span>
                                @elseif($isCompleted && $key === 'delivered' && $shipment && $shipment->delivered_at)
                                    <span class="text-[10px] font-mono text-emerald-400">
                                        {{ $shipment->delivered_at->format('M d, H:i') }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-hoyos-muted mt-1 leading-relaxed">
                                {{ $stage['desc'] }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Disclaimer as explicitly required by product specification -->
            <div class="mt-8 pt-4 border-t border-hoyos-border/50 flex items-start gap-2.5 text-[11px] text-hoyos-dim">
                <svg class="w-4 h-4 text-hoyos-muted shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p>
                    <strong>Notice:</strong> Status milestones displayed above are maintained internally by Hoyos fulfillment personnel. Tracking codes are issued through J&T Express. For live GPS courier telemetry, tap "Track on Official J&T Portal".
                </p>
            </div>
        </div>

        <!-- Destination & Parcel Details -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-hoyos-surface border border-hoyos-border rounded-2xl p-6">
                <h4 class="text-xs font-semibold text-white uppercase tracking-[0.04em] mb-4 pb-2 border-b border-hoyos-border">
                    Destination Information
                </h4>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 text-hoyos-muted">
                        <span>Recipient</span>
                        <span class="text-white font-medium">{{ $order->customer_name }}</span>
                    </div>
                    <div class="flex justify-between py-1 text-hoyos-muted">
                        <span>Phone</span>
                        <span class="text-white font-mono">{{ $order->customer_phone }}</span>
                    </div>
                    <div class="flex justify-between py-1 text-hoyos-muted">
                        <span>Province / City</span>
                        <span class="text-white">{{ $order->province }}</span>
                    </div>
                    <div class="py-1 text-hoyos-muted">
                        <span class="block mb-1">Address:</span>
                        <span class="text-white">{{ $order->shipping_address }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-hoyos-surface border border-hoyos-border rounded-2xl p-6">
                <h4 class="text-xs font-semibold text-white uppercase tracking-[0.04em] mb-4 pb-2 border-b border-hoyos-border">
                    Package Contents ({{ $order->items->sum('quantity') }} items)
                </h4>
                <div class="space-y-3 max-h-48 overflow-y-auto pr-1">
                    @foreach($order->items->sortBy(fn($i) => \App\Models\ProductVariant::sizeOrderIndex($i->variant_size ?? $i->size)) as $item)
                        <div class="flex items-center gap-3 text-xs">
                            <span class="w-6 h-6 rounded bg-hoyos-card border border-hoyos-border flex items-center justify-center font-mono font-bold text-white text-[10px] shrink-0">
                                {{ $item->quantity }}x
                            </span>
                            <div class="flex-1 truncate">
                                <span class="text-white truncate block">{{ $item->product_title }}</span>
                                <span class="text-[10px] text-hoyos-dim">Size: {{ $item->size }}</span>
                            </div>
                            <span class="font-mono text-hoyos-muted shrink-0">
                                ${{ number_format($item->total_price, 2) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-hoyos-layout>
