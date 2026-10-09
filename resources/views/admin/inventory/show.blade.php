@extends('layouts.admin')

@section('title', 'Inventory: ' . $variant->sku)

@section('content')
<div 
    class="space-y-6 max-w-7xl mx-auto" 
    x-data="{ 
        confirmAdjustOpen: false 
    }"
>
    <!-- 1. Page Header with Breadcrumb Back link -->
    <x-ui.page-header
        eyebrow="SKU Stock Inspection"
        title="{{ $variant->product?->name ?? 'Product' }} — Size {{ $variant->size }}"
        subtitle="Manage individual variant inventory units, log physical discrepancies, and inspect ledger entries."
    >
        <x-slot:status>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.inventory.index') }}" class="inline-flex items-center gap-1.5 text-xs font-mono text-zinc-400 hover:text-white transition-colors">
                    <span>&larr;</span>
                    <span>Back to Inventory</span>
                </a>

                @php
                    $stock = $variant->stock_quantity;
                    $statusKey = $stock <= 0 ? 'out_of_stock' : ($stock <= 5 ? 'low_stock' : 'in_stock');
                    $statusLabel = $stock <= 0 ? 'Out of Stock' : ($stock <= 5 ? 'Low Stock' : 'In Stock');
                @endphp

                <x-ui.status-badge :status="$statusKey" :label="$statusLabel" size="md" />
            </div>
        </x-slot:status>
    </x-ui.page-header>

    <!-- Top Variant Meta Pill / Summary Bar -->
    <div class="glass rounded-2xl p-4 sm:p-5 border border-white/[0.08] shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2 sm:gap-3 text-xs font-mono text-zinc-400">
            <span>SKU: <strong class="text-white">{{ $variant->sku }}</strong></span>
            <span class="text-zinc-600">•</span>
            <span>Category: <span class="text-zinc-200">{{ $variant->product?->category?->name ?? '—' }}</span></span>
            <span class="text-zinc-600">•</span>
            <span>Effective Price: <span class="text-white font-bold">${{ number_format($variant->effective_price, 2) }}</span></span>
            @if($variant->product)
                <span class="text-zinc-600">•</span>
                <a href="{{ route('admin.products.edit', $variant->product) }}" class="text-hoyos-red hover:underline font-sans text-xs">
                    View Product Definition &rarr;
                </a>
            @endif
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto bg-zinc-950/80 border border-white/[0.08] rounded-xl px-3 py-1.5 shrink-0">
            <span class="text-[10px] font-mono uppercase tracking-wider text-admin-muted">Current Stock:</span>
            <span class="text-base font-bold font-mono text-white tabular-nums">{{ $variant->stock_quantity }}</span>
            <span class="text-xs font-mono text-zinc-400">units</span>
        </div>
    </div>

    <!-- 2-Column Grid: Left (Stock Adjustment) | Right (Audit Ledger) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Left Column: 5 Cols — Stock Adjustment Form -->
        <div class="lg:col-span-5 space-y-6">
            <x-ui.glass-card 
                title="Stock Adjustment" 
                description="Log physical warehouse restock, shrinkage, or damaged unit reconciliations."
            >
                <form id="adjust-form" method="POST" action="{{ route('admin.inventory.adjust', $variant) }}" class="space-y-4">
                    @csrf

                    <!-- Adjustment Type -->
                    <x-ui.form-field 
                        label="Adjustment Type" 
                        name="type" 
                        :required="true"
                    >
                        <select 
                            name="type" 
                            id="type"
                            required
                            class="w-full py-2.5 px-3 text-xs rounded-xl bg-white/[0.03] border border-white/[0.1] text-zinc-200 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all font-mono"
                        >
                            <option value="manual_restock" {{ old('type') === 'manual_restock' ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Manual Restock (+ Add Stock)</option>
                            <option value="manual_shrinkage" {{ old('type') === 'manual_shrinkage' ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Damaged / Shrinkage (- Remove Stock)</option>
                            <option value="damage" {{ old('type') === 'damage' ? 'selected' : '' }} class="bg-zinc-900 text-zinc-300">Inventory Count Correction / Defect (- Deduct Stock)</option>
                        </select>
                    </x-ui.form-field>

                    <!-- Quantity Units -->
                    <x-ui.form-field 
                        label="Quantity Units" 
                        name="quantity" 
                        :required="true"
                    >
                        <input 
                            type="number" 
                            name="quantity" 
                            id="quantity"
                            min="1" 
                            required
                            value="{{ old('quantity', 1) }}"
                            placeholder="e.g. 5"
                            class="w-full bg-white/[0.03] border border-white/[0.1] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-xl px-3.5 py-2.5 text-xs font-mono text-zinc-100 placeholder:text-admin-muted focus:outline-none transition-all"
                        />
                    </x-ui.form-field>

                    <!-- Adjustment Reason & Notes -->
                    <x-ui.form-field 
                        label="Adjustment Reason & Notes" 
                        name="reason" 
                        :required="true"
                        helper="Minimum 5 characters required for administrative audit trail."
                    >
                        <textarea 
                            name="reason" 
                            id="reason"
                            rows="4" 
                            required 
                            minlength="5"
                            placeholder="Detailed explanation for audit ledger (minimum 5 characters)..."
                            class="w-full bg-white/[0.03] border border-white/[0.1] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-xl p-3 text-xs font-sans text-zinc-100 placeholder:text-admin-muted focus:outline-none transition-all"
                        >{{ old('reason') }}</textarea>
                    </x-ui.form-field>
                </form>

                <!-- Plain Footer Row for Save/Cancel inside GlassCard -->
                <div class="mt-6 pt-4 border-t border-white/[0.08] flex items-center justify-between gap-3">
                    <x-ui.button 
                        :href="route('admin.inventory.index')" 
                        variant="secondary" 
                        size="sm"
                        class="font-sans"
                    >
                        Cancel
                    </x-ui.button>
                    <x-ui.button 
                        type="button" 
                        @click="confirmAdjustOpen = true"
                        variant="primary" 
                        size="sm"
                        class="font-sans"
                    >
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Apply Stock Adjustment</span>
                    </x-ui.button>
                </div>
            </x-ui.glass-card>
        </div>

        <!-- Right Column: 7 Cols — Stock Adjustments Ledger -->
        <div class="lg:col-span-7">
            <x-ui.glass-card>
                <x-slot:header>
                    <div class="flex items-center justify-between gap-4 border-b border-white/[0.08] pb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-hoyos-red"></span>
                                <h3 class="text-xs font-mono font-semibold tracking-wider text-zinc-200 uppercase">
                                    Stock Adjustments Ledger
                                </h3>
                            </div>
                            <p class="text-xs text-zinc-400 font-sans leading-relaxed mt-0.5">
                                Immutable audit record of all inventory deductions and manual restocks.
                            </p>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-mono text-zinc-400 bg-zinc-900 border border-white/[0.08] shrink-0">
                            {{ $variant->stockAdjustments->count() }} {{ Str::plural('entry', $variant->stockAdjustments->count()) }}
                        </span>
                    </div>
                </x-slot:header>

                @if($variant->stockAdjustments->isEmpty())
                    <div class="py-12 px-6 text-center">
                        <div class="w-12 h-12 mx-auto rounded-full bg-zinc-900 border border-white/[0.08] flex items-center justify-center text-admin-muted mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-white font-mono">No Ledger History</h4>
                        <p class="text-xs text-zinc-400 mt-1 max-w-sm mx-auto">
                            No stock adjustments recorded for this variant yet.
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto -mx-5 sm:-mx-6 -mb-5 sm:-mb-6 mt-2">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-zinc-950/60 border-b border-white/[0.08] text-[10px] font-mono uppercase tracking-wider text-zinc-400">
                                    <th class="px-5 py-3 font-sans">DATE</th>
                                    <th class="px-4 py-3 font-sans">TYPE</th>
                                    <th class="px-4 py-3 text-center font-sans">QUANTITY (+/-)</th>
                                    <th class="px-5 py-3 font-sans">REASON / ADMIN</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/[0.04]">
                                @foreach($variant->stockAdjustments as $adj)
                                    <tr class="hover:bg-white/[0.02] transition-colors">
                                        <!-- DATE -->
                                        <td class="px-5 py-3.5 font-mono text-[11px] text-zinc-400 whitespace-nowrap">
                                            <x-ui.time :value="$adj->created_at" />
                                        </td>

                                        <!-- TYPE -->
                                        <td class="px-4 py-3.5">
                                            @php
                                                $typeColors = [
                                                    'manual_restock' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                                    'restock' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                                    'manual_shrinkage' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                                    'shrinkage' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                                    'damage' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                                    'order_reserved' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                                    'reserved' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                                    'order_cancelled' => 'bg-zinc-800/80 text-zinc-300 border-white/[0.08]',
                                                    'payment_expired' => 'bg-zinc-800/80 text-zinc-400 border-white/[0.06]',
                                                    'audit' => 'bg-zinc-800/80 text-zinc-400 border-white/[0.06]',
                                                    'return' => 'bg-zinc-800/80 text-zinc-400 border-white/[0.06]',
                                                ];
                                                $tClass = $typeColors[$adj->adjustment_type] ?? 'bg-zinc-800/80 text-zinc-400 border-white/[0.06]';
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono uppercase font-medium border {{ $tClass }}">
                                                {{ str_replace('_', ' ', $adj->adjustment_type) }}
                                            </span>
                                            <div class="text-[10px] font-mono text-admin-muted mt-1">
                                                {{ $adj->quantity_before }} &rarr; {{ $adj->quantity_after }}
                                            </div>
                                        </td>

                                        <!-- QUANTITY (+/-) in mono with +/- colour -->
                                        <td class="px-4 py-3.5 text-center font-mono font-bold text-xs whitespace-nowrap">
                                            <span class="{{ $adj->quantity_change > 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                                                {{ $adj->quantity_change > 0 ? '+' : '' }}{{ $adj->quantity_change }}
                                            </span>
                                        </td>

                                        <!-- REASON / ADMIN -->
                                        <td class="px-5 py-3.5 font-sans">
                                            <div class="text-xs text-zinc-200 line-clamp-2">
                                                {{ $adj->reason ?? '—' }}
                                            </div>
                                            <div class="text-[10px] font-mono text-admin-muted mt-1 flex items-center gap-1.5">
                                                <span>{{ $adj->admin ? $adj->admin->name . ' (Admin)' : 'System' }}</span>
                                                @if($adj->order)
                                                    <span class="text-zinc-600">•</span>
                                                    <a href="{{ route('admin.orders.show', $adj->order) }}" class="text-hoyos-red hover:underline">
                                                        #{{ $adj->order->order_number }}
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-ui.glass-card>
        </div>
    </div>

    <!-- ConfirmModal for Stock Adjustment -->
    <x-ui.confirm-modal
        name="confirmAdjustOpen"
        title="Confirm Stock Adjustment"
        description="Are you sure you want to apply this manual stock adjustment to SKU {{ $variant->sku }}? This change immediately alters live storefront inventory and is written to the immutable audit ledger."
        confirmText="Apply Adjustment"
        variant="primary"
        :customFooter="true"
    >
        <div class="flex items-center justify-end gap-3 pt-2">
            <x-ui.button type="button" @click="confirmAdjustOpen = false" variant="ghost" size="sm" class="font-sans">
                Cancel
            </x-ui.button>
            <x-ui.button type="submit" form="adjust-form" variant="primary" size="sm" class="font-sans">
                Confirm & Apply
            </x-ui.button>
        </div>
    </x-ui.confirm-modal>
</div>
@endsection
