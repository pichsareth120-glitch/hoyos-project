@props([
    'name' => null,
    'value' => null,
    'title' => null,
    'description' => null,
    'badge' => null,
    'selected' => false,
])

<div 
    {{ $attributes->merge([
        'class' => 'relative rounded-2xl p-4 sm:p-5 glass cursor-pointer transition-all duration-200 select-none group focus-ring'
    ]) }}
>
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-start gap-3.5 flex-1 min-w-0">
            <!-- Custom Radio Circle with Check Indicator -->
            <div 
                class="w-5 h-5 rounded-full border flex items-center justify-center shrink-0 mt-0.5 transition-all duration-200"
                :class="{{ $selected }} ? 'border-hoyos-red bg-hoyos-red text-white shadow-[0_0_12px_rgba(255,0,46,0.6)]' : 'border-white/20 bg-white/[0.04] group-hover:border-white/40'"
            >
                <div 
                    class="w-2 h-2 rounded-full bg-white transition-transform duration-200"
                    :class="{{ $selected }} ? 'scale-100' : 'scale-0'"
                ></div>
            </div>

            <div class="space-y-1 flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <p class="text-xs font-mono font-bold uppercase tracking-wider text-white truncate">
                        {{ $title }}
                    </p>
                    @if($badge)
                        <span class="text-[9px] font-mono font-bold uppercase px-2 py-0.5 rounded-full bg-hoyos-red/20 text-hoyos-red border border-hoyos-red/40">
                            {{ $badge }}
                        </span>
                    @endif
                </div>

                @if($description)
                    <p class="text-xs text-white/60 leading-relaxed font-sans">
                        {{ $description }}
                    </p>
                @endif

                {{ $slot }}
            </div>
        </div>
    </div>
</div>
