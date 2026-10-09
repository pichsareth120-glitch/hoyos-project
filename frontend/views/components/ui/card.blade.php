@props([
    'title' => null,
    'description' => null,
    'interactive' => false,
    'variant' => 'default', // 'default', 'elevated', 'subtle', 'danger', 'selected'
    'glass' => null,
])

@php
    $effectiveVariant = $glass ?? $variant ?? 'default';

    $hasCustomPadding = isset($attributes['class']) && preg_match('/(^|\s)p-\d+/', $attributes['class']);
    
    $variantClass = match($effectiveVariant) {
        'elevated' => 'glass-elevated',
        'subtle' => 'glass-subtle',
        'danger', 'red' => 'glass-danger',
        'selected' => 'glass-selected',
        default => 'glass',
    };

    $interactiveClass = $interactive ? 'glass-interactive cursor-pointer' : '';
@endphp

<div {{ $attributes->merge(['class' => "rounded-2xl text-white {$variantClass} {$interactiveClass} " . ($hasCustomPadding ? '' : 'p-5 sm:p-6')]) }}>
    @if(isset($header))
        <div class="mb-4">
            {{ $header }}
        </div>
    @elseif($title || $description)
        <div class="space-y-1 mb-4">
            @if($title)
                <h3 class="text-xs font-mono font-bold tracking-wider text-white uppercase">
                    {{ $title }}
                </h3>
            @endif
            @if($description)
                <p class="text-xs text-white/60 font-sans leading-relaxed">
                    {{ $description }}
                </p>
            @endif
        </div>
    @endif

    {{ $slot }}

    @if(isset($footer))
        <div class="pt-4 mt-auto border-t border-white/[0.08]">
            {{ $footer }}
        </div>
    @endif
</div>
