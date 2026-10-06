@props(['class' => 'w-[120px]'])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <img 
        src="{{ asset('images/hoyos/hoyos-wordmark.svg') }}" 
        alt="HOYOS" 
        class="w-[120px] h-auto object-contain drop-shadow-[0_0_12px_rgba(255,0,46,0.6)]"
    />
</div>
