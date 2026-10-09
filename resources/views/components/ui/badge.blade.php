@props([
    'variant' => 'neutral',
    'size' => 'sm',
    'dot' => false,
])

@php
    $variantClasses = match($variant) {
        'paid', 'success' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/40 shadow-[0_0_12px_rgba(34,197,94,0.18)]',
        'pending', 'warning' => 'bg-amber-500/15 text-amber-300 border-amber-500/35 shadow-[0_0_12px_rgba(245,179,1,0.18)]',
        'confirmed', 'order_confirmed', 'crimson', 'primary' => 'bg-hoyos-red/15 text-white border-hoyos-red/45 shadow-[0_0_14px_rgba(255,0,46,0.25)]',
        'default', 'default_address' => 'bg-hoyos-red text-white border-transparent font-bold shadow-[0_0_14px_rgba(255,0,46,0.45)]',
        'danger', 'alert', 'cancelled' => 'bg-red-500/15 text-red-400 border-red-500/40',
        'info', 'shipped' => 'bg-sky-500/15 text-sky-400 border-sky-500/40',
        'outline' => 'bg-transparent text-white/80 border-white/20',
        'glow' => 'bg-hoyos-red/20 text-white border-hoyos-red/40 shadow-[0_0_12px_rgba(255,0,46,0.3)]',
        default => 'glass text-white/80 border-white/10',
    };

    $dotColor = match($variant) {
        'paid', 'success' => 'bg-emerald-400',
        'pending', 'warning' => 'bg-amber-400',
        'confirmed', 'order_confirmed', 'crimson', 'primary', 'default' => 'bg-hoyos-red',
        'danger', 'alert', 'cancelled' => 'bg-red-400',
        'info', 'shipped' => 'bg-sky-400',
        default => 'bg-white/60',
    };

    $sizeClasses = match($size) {
        'xs' => 'px-2 py-0.5 text-[10px]',
        'md' => 'px-3.5 py-1 text-xs',
        default => 'px-2.5 py-0.5 text-[11px]',
    };
@endphp

<span {{ $attributes->merge(['class' => "rounded-full font-mono font-bold uppercase border inline-flex items-center gap-1.5 tracking-wider select-none {$sizeClasses} {$variantClasses}"]) }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }} animate-pulse"></span>
    @endif
    {{ $slot }}
</span>
