@props([
    'title',
    'value',
    'subtext' => null,
    'description' => null,
    'trend' => null,
    'trendLabel' => null,
    'trendPositive' => null,
    'icon' => null,
    'href' => null,
])

@php
    $displaySubtext = $subtext ?? $description;
    $isPositive = $trendPositive ?? ($trend !== null ? ((float)$trend >= 0) : null);
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} 
    @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => "group relative block rounded-2xl glass p-3.5 sm:p-5 lg:p-6 overflow-hidden focus-ring transition-all duration-200 hover:-translate-y-0.5 hover:border-white/[0.18] hover:shadow-[inset_0_1px_0_rgba(255,255,255,0.08),0_12px_36px_rgba(0,0,0,0.55)]"]) }}
>
    <!-- Thin Top Accent Line: 1px gradient, red at center fading to transparent at edges -->
    <div class="absolute inset-x-0 top-0 h-[1px] bg-gradient-to-r from-transparent via-[#FF002E]/60 to-transparent pointer-events-none"></div>

    <div class="relative z-10 flex items-start justify-between gap-2.5 sm:gap-3">
        <span class="text-[10px] sm:text-[11px] font-mono font-semibold tracking-wider text-zinc-400 uppercase leading-snug">
            {{ $title }}
        </span>
        @if($icon)
            <div class="relative shrink-0">
                <!-- Soft Glow Behind Icon Chip -->
                <div class="absolute inset-0 rounded-xl bg-hoyos-red/20 blur-md pointer-events-none -z-10 group-hover:bg-hoyos-red/35 transition-all duration-200"></div>
                <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-white/[0.04] border border-white/10 flex items-center justify-center text-zinc-300 shadow-[0_0_16px_rgba(255,0,46,0.18)] group-hover:border-hoyos-red/40 group-hover:text-hoyos-red group-hover:bg-hoyos-red/10 group-hover:shadow-[0_0_20px_rgba(255,0,46,0.35)] transition-all duration-200">
                    {{ $icon }}
                </div>
            </div>
        @endif
    </div>

    <div class="relative z-10 mt-2.5 sm:mt-4 space-y-1 sm:space-y-1.5">
        <div class="text-lg sm:text-2xl xl:text-3xl font-mono font-bold tracking-tight text-white tabular-nums truncate">
            {{ $value }}
        </div>

        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 text-[11px] sm:text-xs">
            @if($trend !== null)
                <span class="inline-flex items-center gap-0.5 px-1.5 sm:px-2 py-0.5 rounded-full text-[9px] sm:text-[10px] font-mono font-bold border {{ $isPositive ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' : 'bg-hoyos-red/15 text-hoyos-red border-hoyos-red/30' }}">
                    <span>{{ $isPositive ? '↑ +' : '↓ ' }}{{ abs($trend) }}%</span>
                </span>
            @endif

            @if($trendLabel)
                <span class="text-zinc-400 font-sans text-[11px] sm:text-xs">{{ $trendLabel }}</span>
            @endif

            @if($displaySubtext)
                <span class="text-zinc-400 font-sans text-[11px] sm:text-xs leading-tight line-clamp-1 sm:line-clamp-none">{{ $displaySubtext }}</span>
            @endif
        </div>
    </div>
</{{ $tag }}>
