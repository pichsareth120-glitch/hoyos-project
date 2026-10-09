@props([
    'eyebrow' => null,
    'title' => null,
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-white/[0.08]']) }}>
    <div>
        @if($eyebrow || isset($eyebrowSlot))
            <div class="flex items-center gap-2 mb-1.5">
                <span class="w-2 h-2 rounded-full bg-hoyos-red shadow-[0_0_8px_#FF002E]"></span>
                <span class="text-[11px] font-mono uppercase tracking-wider text-hoyos-red font-semibold">
                    {{ $eyebrowSlot ?? $eyebrow }}
                </span>
            </div>
        @endif

        <div class="flex items-center gap-3 flex-wrap">
            @if(isset($titleSlot))
                {{ $titleSlot }}
            @elseif($title)
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white font-display uppercase leading-tight">
                    {{ $title }}
                </h1>
            @endif
        </div>

        @if($subtitle || isset($subtitleSlot))
            <p class="text-xs sm:text-[13px] text-zinc-300 font-sans leading-relaxed mt-1">
                {{ $subtitleSlot ?? $subtitle }}
            </p>
        @endif
    </div>

    @if(isset($actions) || isset($status) || isset($statusPill))
        <div class="flex items-center gap-3 flex-wrap">
            {{ $statusPill ?? $status ?? '' }}
            {{ $actions ?? '' }}
        </div>
    @endif
</div>
