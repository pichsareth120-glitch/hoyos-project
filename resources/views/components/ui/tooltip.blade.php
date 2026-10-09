@props([
    'text' => '',
    'position' => 'top', // 'top', 'bottom', 'left', 'right'
])

@php
    $positionClasses = match($position) {
        'bottom' => 'top-full left-1/2 -translate-x-1/2 mt-2',
        'left' => 'right-full top-1/2 -translate-y-1/2 mr-2',
        'right' => 'left-full top-1/2 -translate-y-1/2 ml-2',
        default => 'bottom-full left-1/2 -translate-x-1/2 mb-2',
    };
@endphp

<div x-data="{ show: false }" class="relative inline-flex items-center" @mouseenter="show = true" @mouseleave="show = false" @focusin="show = true" @focusout="show = false">
    {{ $slot }}
    
    <div 
        x-show="show" 
        x-cloak 
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute {{ $positionClasses }} px-2.5 py-1 text-[11px] font-mono text-zinc-200 bg-[#16060c]/98 border border-white/[0.12] rounded-lg shadow-xl pointer-events-none whitespace-nowrap z-50 shadow-[inset_0_1px_0_0_rgba(255,255,255,0.08)]"
    >
        {{ $text }}
    </div>
</div>
