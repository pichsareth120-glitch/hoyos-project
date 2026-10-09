<x-admin-layout title="Invoice INV-{{ $order->created_at->format('Ymd') }}-{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}">
    @php
        $invoiceNumber = 'INV-' . $order->created_at->format('Ymd') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);

        $paymentMethodLabel = match($order->payment_method) {
            'aba_khqr' => 'ABA KHQR (Bakong / National QR)',
            'cod' => 'Cash on Delivery (COD)',
            default => strtoupper($order->payment_method ?? 'N/A'),
        };
    @endphp

    <style>
        @media print {
            aside, header, .no-print, nav {
                display: none !important;
            }
            body, html {
                background: #ffffff !important;
                color: #000000 !important;
            }
            main {
                padding: 0 !important;
                margin: 0 !important;
            }
            .invoice-printable-card {
                background: #ffffff !important;
                color: #000000 !important;
                border: 1px solid #cccccc !important;
                box-shadow: none !important;
                backdrop-filter: none !important;
                -webkit-backdrop-filter: none !important;
                padding: 1.5rem !important;
                max-width: 100% !important;
            }
            .invoice-printable-card * {
                color: #000000 !important;
                border-color: #e5e5e5 !important;
                background-color: transparent !important;
                box-shadow: none !important;
                text-shadow: none !important;
            }
            .invoice-box {
                border: 1px solid #e5e5e5 !important;
                background: #fafafa !important;
            }
            .invoice-box * {
                color: #000000 !important;
            }
            .invoice-pill {
                border: 1px solid #000000 !important;
                background: #f0f0f0 !important;
                color: #000000 !important;
            }
            .invoice-pill * {
                color: #000000 !important;
            }
            .invoice-pill .rounded-full {
                display: none !important;
            }
            table, thead, tbody, tr, th, td {
                border-color: #e5e5e5 !important;
                color: #000000 !important;
            }
        }
    </style>

    <div class="space-y-6 max-w-5xl mx-auto">
        <!-- Top Sticky Action Bar (Hidden on print) -->
        <x-ui.action-bar class="no-print">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <x-ui.button 
                        :href="route('admin.invoices.index')" 
                        variant="secondary" 
                        size="sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Back to Invoices</span>
                    </x-ui.button>
                    <span class="text-xs font-mono text-zinc-600">/</span>
                    <span class="text-xs font-mono text-white font-bold">{{ $invoiceNumber }}</span>
                </div>

                <div class="flex items-center gap-2.5 shrink-0">
                    <x-ui.button 
                        :href="route('admin.orders.show', $order)" 
                        variant="secondary" 
                        size="sm"
                    >
                        <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <span>Order #{{ $order->order_number }}</span>
                    </x-ui.button>

                    <x-ui.button 
                        onclick="window.print()" 
                        variant="primary" 
                        size="sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Print Invoice</span>
                    </x-ui.button>
                </div>
            </div>
        </x-ui.action-bar>

        <!-- Printable Invoice Document Sheet (Glass in UI, Clean White in Print) -->
        <x-ui.glass-card class="invoice-printable-card p-6 sm:p-10 space-y-8">
            <!-- Brand & Invoice Header -->
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-6 pb-6 border-b border-white/[0.08]">
                <div>
                    <div class="flex items-center gap-2.5 mb-2">
                        <span class="w-3 h-3 rounded-full bg-hoyos-red shadow-[0_0_10px_#FF002E]"></span>
                        <span class="font-display text-3xl tracking-wider text-white">HOYOS</span>
                        <span class="px-2 py-0.5 rounded text-[9px] font-mono font-bold uppercase tracking-widest bg-hoyos-red/20 text-hoyos-red border border-hoyos-red/30">
                            Official Receipt
                        </span>
                    </div>
                    <div class="text-xs text-zinc-400 space-y-0.5 font-mono">
                        <p class="text-white font-medium">HOYOS Cyberpunk & Gothic Streetwear Ltd.</p>
                        <p>Toul Tompoung 1, Chamkarmon, Phnom Penh, Cambodia</p>
                        <p>Support: <span class="text-white">support@hoyos.com</span> &bull; Phone: +855 12 999 888</p>
                    </div>
                </div>

                <div class="sm:text-right space-y-1 font-mono">
                    <div class="text-xs text-zinc-400 uppercase tracking-wider font-semibold">TAX INVOICE / RECEIPT</div>
                    <div class="text-xl font-bold text-white tracking-wide">{{ $invoiceNumber }}</div>
                    <div class="text-xs text-zinc-400">Date: <span class="text-white"><x-ui.time :value="$order->created_at" /></span></div>
                    <div class="text-xs text-zinc-400">Order Ref: <span class="text-white">#{{ $order->order_number }}</span></div>
                    <div class="pt-1">
                        <x-ui.status-badge :status="$order->payment_status" size="sm" class="invoice-pill">
                            Payment: {{ ucfirst($order->payment_status) }}
                        </x-ui.status-badge>
                    </div>
                </div>
            </div>

            <!-- Billed To & Delivery Addresses -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-6 border-b border-white/[0.08] text-xs">
                <!-- Billed To -->
                <div class="invoice-box p-4 rounded-xl bg-white/[0.02] border border-white/[0.08] space-y-2">
                    <div class="font-mono text-[10px] uppercase tracking-wider text-zinc-400 font-bold flex items-center justify-between">
                        <span>Billed To (Customer)</span>
                        @if($order->user_id)
                            <span class="text-emerald-400 font-mono">Account #{{ $order->user_id }}</span>
                        @else
                            <span class="text-admin-muted font-mono">Guest Checkout</span>
                        @endif
                    </div>
                    <div class="font-bold text-sm text-white font-sans">{{ $order->customer_name }}</div>
                    <div class="space-y-0.5 text-zinc-400 font-mono">
                        <div>Email: <span class="text-white">{{ $order->customer_email }}</span></div>
                        <div>Phone: <span class="text-white">{{ $order->customer_phone ?? 'N/A' }}</span></div>
                    </div>
                </div>

                <!-- Shipping & Delivery Details -->
                <div class="invoice-box p-4 rounded-xl bg-white/[0.02] border border-white/[0.08] space-y-2">
                    <div class="font-mono text-[10px] uppercase tracking-wider text-zinc-400 font-bold flex items-center justify-between">
                        <span>Shipping & Delivery Destination</span>
                        <span class="text-zinc-200 font-sans font-medium">{{ $order->shipment?->carrier ?? 'Standard Shipping' }}</span>
                    </div>
                    <div class="text-zinc-200 font-medium leading-relaxed font-sans">
                        {{ $order->shipping_address }}
                    </div>
                    <div class="text-zinc-400 font-mono space-y-0.5">
                        <div>City / Province: <span class="text-white">{{ $order->province ?? $order->shipping_city }}</span></div>
                        @if($order->shipment?->tracking_number)
                            <div>Tracking No: <span class="text-white font-bold">{{ $order->shipment->tracking_number }}</span></div>
                        @endif
                        @if($order->delivery_notes)
                            <div class="text-[11px] text-admin-muted italic font-sans pt-1">Notes: "{{ $order->delivery_notes }}"</div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Line Items Table -->
            <div class="space-y-3">
                <div class="text-xs font-mono uppercase tracking-wider text-zinc-400 font-bold">
                    Purchased Line Items
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-zinc-950/40 border-y border-white/[0.08] font-mono text-[10px] uppercase tracking-wider text-zinc-400">
                            <tr>
                                <th class="py-2.5 px-3">#</th>
                                <th class="py-2.5 px-3">Description / Item</th>
                                <th class="py-2.5 px-3 text-center">Size / SKU</th>
                                <th class="py-2.5 px-3 text-right">Unit Price</th>
                                <th class="py-2.5 px-3 text-center">Qty</th>
                                <th class="py-2.5 px-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.04]">
                            @foreach($order->items as $index => $item)
                                <tr>
                                    <td class="py-3 px-3 font-mono text-admin-muted">{{ $index + 1 }}</td>
                                    <td class="py-3 px-3">
                                        <div class="font-bold text-white font-sans">{{ $item->product_name }}</div>
                                        @if($item->variant?->product?->category)
                                            <div class="text-[10px] text-zinc-400 font-mono mt-0.5">Category: {{ $item->variant->product->category->name }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 text-center font-mono">
                                        <span class="px-2 py-0.5 rounded bg-white/[0.04] border border-white/[0.08] text-white text-[11px]">
                                            {{ $item->variant_size ?? 'STD' }}
                                        </span>
                                        @if($item->variant?->sku)
                                            <div class="text-[9px] text-admin-muted mt-0.5">{{ $item->variant->sku }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono text-white tabular-nums">
                                        ${{ number_format($item->unit_price, 2) }}
                                    </td>
                                    <td class="py-3 px-3 text-center font-mono text-white font-bold">
                                        {{ $item->quantity }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono text-white font-bold tabular-nums">
                                        ${{ number_format($item->subtotal, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Financial Summary & Payment Breakdown -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-white/[0.08]">
                <!-- Payment Method & Audit Information -->
                <div class="invoice-box p-4 rounded-xl bg-white/[0.02] border border-white/[0.08] space-y-2 text-xs">
                    <div class="font-mono text-[10px] uppercase tracking-wider text-zinc-400 font-bold">
                        Payment & Settlement Method
                    </div>
                    <div class="font-semibold text-white font-mono uppercase">{{ $paymentMethodLabel }}</div>
                    <div class="text-zinc-400 font-mono space-y-1 text-[11px]">
                        @if($order->latestPayment)
                            <div>Transaction Ref: <span class="text-white">{{ $order->latestPayment->transaction_id ?? $order->latestPayment->transaction_reference ?? 'N/A' }}</span></div>
                            <div>Currency: <span class="text-white">{{ $order->latestPayment->currency ?? 'USD' }}</span></div>
                            <div>Payment Timestamp: <span class="text-white">@if($order->latestPayment->paid_at)<x-ui.time :value="$order->latestPayment->paid_at" />@else Pending settlement @endif</span></div>
                        @else
                            <div class="text-admin-muted italic">No electronic transaction record logged.</div>
                        @endif
                    </div>
                </div>

                <!-- Financial Calculation Totals -->
                <div class="space-y-2 text-xs font-mono">
                    <div class="flex items-center justify-between py-1 text-zinc-400">
                        <span>Items Subtotal:</span>
                        <span class="text-white font-medium tabular-nums">${{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1 text-zinc-400">
                        <span>Delivery & Shipping Fee:</span>
                        <span class="text-white font-medium tabular-nums">${{ number_format($order->shipping_fee ?? $order->shipping_cost ?? 0, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-t-2 border-white/[0.08] text-sm font-bold text-white">
                        <span class="uppercase">Invoice Total Due:</span>
                        <span class="text-lg text-hoyos-red font-display tracking-wider tabular-nums">${{ number_format($order->total_amount, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1 text-xs text-emerald-400">
                        <span>Amount Settled / Paid:</span>
                        <span class="font-bold tabular-nums">
                            ${{ $order->payment_status === 'paid' ? number_format($order->total_amount, 2) : '0.00' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Footer Sign-off Note -->
            <div class="pt-6 border-t border-white/[0.08] text-center text-[10px] font-mono text-admin-muted space-y-1">
                <p>Thank you for choosing HOYOS. All garments are designed with raw aesthetic precision.</p>
                <p>This document serves as an official sales tax invoice and delivery packing receipt.</p>
            </div>
        </x-ui.glass-card>
    </div>
</x-admin-layout>
