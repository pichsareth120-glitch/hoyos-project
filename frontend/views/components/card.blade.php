@props([
    'title' => null,
    'description' => null,
])

@php
    $hasCustomPadding = isset($attributes['class']) && preg_match('/(^|\s)p-\d+/', $attributes['class']);
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl glass glass-hover text-zinc-100 transition-all duration-200 ' . ($hasCustomPadding ? '' : 'p-5 sm:p-6')]) }}>
    @if(isset($header))
        <div class="mb-4">
            {{ $header }}
        </div>
    @elseif($title || $description)
        <div class="space-y-1 mb-4">
            @if($title)
                <h3 class="text-xs font-mono font-semibold tracking-wider text-zinc-300 uppercase">
                    {{ $title }}
                </h3>
            @endif
            @if($description)
                <p class="text-xs text-zinc-400 font-sans leading-relaxed">
                    {{ $description }}
                </p>
            @endif
        </div>
    @endif

    {{ $slot }}

    @if(isset($footer))
        <div class="pt-4 mt-4 border-t border-white/[0.06]">
            {{ $footer }}
        </div>
    @endif
</div>
