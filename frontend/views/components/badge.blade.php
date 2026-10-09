@props([
    'variant' => 'neutral',
])

@php
    $variantClasses = match($variant) {
        'danger', 'alert' => 'bg-hoyos-red/15 text-hoyos-red border-hoyos-red/35',
        'warning' => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
        'success' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
        'crimson', 'primary' => 'bg-hoyos-red text-white border-transparent shadow-[0_0_12px_rgba(255,0,46,0.45)]',
        'outline' => 'bg-transparent text-zinc-300 border-white/[0.14]',
        default => 'bg-white/[0.04] text-zinc-300 border-white/[0.08]',
    };
@endphp

<span {{ $attributes->merge(['class' => "px-2.5 py-0.5 rounded-full text-[10px] font-mono font-medium border inline-flex items-center gap-1.5 backdrop-blur-sm tracking-wide {$variantClasses}"]) }}>
    {{ $slot }}
</span>
