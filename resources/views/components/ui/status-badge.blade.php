@props([
    'status' => 'neutral',
    'size' => 'sm',
    'label' => null,
])

@php
    $normalized = strtolower(trim((string)$status));

    $displayLabel = $label ?: \App\Support\LabelHelper::humanize($normalized);

    $config = match($normalized) {
        'paid', 'success', 'confirmed', 'delivered', 'active', 'in_stock', 'create', 'store', 'created', 'promote_primary' => [
            'class' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
            'dot' => 'bg-emerald-400 shadow-[0_0_6px_#34D399]',
        ],
        'pending', 'warning', 'low_stock', 'update', 'updated', 'demote_primary', 'reorder' => [
            'class' => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
            'dot' => 'bg-amber-400 shadow-[0_0_6px_#FBBF24]',
        ],
        'processing', 'shipped', 'in_transit', 'dispatch', 'logistics', 'order_confirmed', 'out_for_delivery', 'out_of_stock' => [
            'class' => 'bg-hoyos-red/15 text-white border-hoyos-red/35',
            'dot' => 'bg-hoyos-red shadow-[0_0_6px_#FF002E]',
        ],
        'archived', 'archive', 'suspended', 'suspend', 'delete', 'deleted', 'destroy', 'cancel' => [
            'class' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
            'dot' => 'bg-rose-400 shadow-[0_0_6px_#F43F5E]',
        ],
        'expired', 'cancelled', 'canceled', 'failed', 'refunded', 'draft', 'inactive' => [
            'class' => 'bg-white/[0.04] text-zinc-400 border-white/[0.08]',
            'dot' => 'bg-zinc-500',
        ],
        default => [
            'class' => 'bg-white/[0.04] text-zinc-300 border-white/[0.08]',
            'dot' => 'bg-zinc-400',
        ],
    };

    $sizeClasses = match($size) {
        'xs' => 'px-1.5 py-0.5 text-[9px]',
        'md' => 'px-3 py-1 text-xs',
        default => 'px-2 py-0.5 text-[10px]',
    };
@endphp

<span {{ $attributes->merge(['class' => "rounded-full font-mono font-medium border inline-flex items-center gap-1.5 tracking-wide {$sizeClasses} {$config['class']}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $config['dot'] }} shrink-0"></span>
    <span>{{ $slot->isEmpty() ? $displayLabel : $slot }}</span>
</span>

