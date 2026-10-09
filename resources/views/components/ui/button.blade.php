@props([
    'variant' => 'default', // 'default' | 'primary' | 'secondary' | 'glass' | 'outline' | 'ghost' | 'destructive'
    'size' => 'md',        // 'xs' | 'sm' | 'md' | 'lg' | 'icon'
    'type' => 'button',
    'href' => null,
    'loading' => false,
])

@php
    $variantClasses = match($variant) {
        'default', 'primary', 'crimson' => 'bg-hoyos-red hover:bg-hoyos-redHover text-white border border-hoyos-red/80 shadow-[0_0_24px_rgba(255,0,46,0.35),inset_0_1px_0_rgba(255,255,255,0.25)] hover:shadow-[0_0_34px_rgba(255,0,46,0.55)]',
        'secondary', 'glass' => 'glass hover:bg-white/[0.08] text-white border border-white/[0.12] hover:border-white/[0.22] shadow-sm',
        'outline' => 'bg-transparent hover:bg-white/[0.05] text-zinc-200 hover:text-white border border-white/[0.15] hover:border-white/[0.25] shadow-sm',
        'ghost' => 'bg-transparent hover:bg-white/[0.06] text-zinc-300 hover:text-white border border-transparent',
        'destructive', 'danger' => 'bg-hoyos-red/15 hover:bg-hoyos-red/25 text-hoyos-red hover:text-red-300 border border-hoyos-red/35 hover:border-hoyos-red/60 shadow-sm',
        default => 'bg-hoyos-red hover:bg-hoyos-redHover text-white border border-hoyos-red shadow-[0_0_24px_rgba(255,0,46,0.35),inset_0_1px_0_rgba(255,255,255,0.25)]',
    };

    $sizeClasses = match($size) {
        'xs' => 'px-2.5 py-1 text-[11px] rounded-lg min-h-[32px]',
        'sm' => 'px-3.5 py-1.5 text-xs rounded-xl min-h-[36px]',
        'md' => 'px-5 py-2.5 text-xs sm:text-sm rounded-xl min-h-[42px]',
        'lg' => 'px-6 py-3 text-sm sm:text-base rounded-2xl min-h-[48px]',
        'icon' => 'w-10 h-10 p-0 rounded-xl flex items-center justify-center shrink-0',
        default => 'px-5 py-2.5 text-xs sm:text-sm rounded-xl min-h-[42px]',
    };

    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }} 
    @if($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    {{ $attributes->merge(['class' => "inline-flex items-center justify-center gap-2 font-mono font-semibold uppercase tracking-wider whitespace-nowrap transition-all duration-200 focus-ring cursor-pointer select-none active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none {$sizeClasses} {$variantClasses}"]) }}
>
    @if($loading)
        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
    @endif
    {{ $slot }}
</{{ $tag }}>
