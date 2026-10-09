@props([
    'variant' => 'outline',
    'type' => 'button',
])

@php
    $variantClasses = match($variant) {
        'ghost' => 'bg-transparent hover:bg-white/[0.05] text-zinc-300 hover:text-white border border-transparent',
        'danger', 'primary' => 'bg-hoyos-red hover:bg-hoyos-redHover text-white border border-hoyos-red shadow-[0_0_20px_rgba(255,0,46,0.35),inset_0_1px_0_rgba(255,255,255,0.25)] hover:shadow-[0_0_28px_rgba(255,0,46,0.55)]',
        'secondary' => 'bg-white/[0.06] hover:bg-white/[0.1] text-zinc-200 border border-white/[0.1]',
        default => 'bg-[#14060a]/80 hover:bg-[#1f0a10] text-zinc-200 hover:text-white border border-white/[0.1] hover:border-white/[0.18] shadow-sm',
    };
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => "inline-flex items-center justify-center max-sm:min-h-[40px] max-sm:min-w-[40px] touch-target px-3.5 py-2 rounded-xl text-xs font-mono font-semibold transition-all duration-150 focus-ring cursor-pointer select-none active:scale-[0.98] {$variantClasses}"]) }}>
    {{ $slot }}
</button>
