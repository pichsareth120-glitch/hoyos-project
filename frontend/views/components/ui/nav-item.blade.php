@props([
    'href',
    'active' => false,
    'badge' => null,
    'badgeVariant' => 'neutral',
    'external' => false,
    'icon' => null,
    'title' => null,
])

<a 
    href="{{ $href }}" 
    @if($external) target="_blank" rel="noopener noreferrer" @endif
    {{ $attributes->merge(['class' => "group relative flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 border focus-ring " . ($active ? 'glass-nav-active text-white border-hoyos-red/40 shadow-[0_0_18px_rgba(255,0,46,0.25)] before:absolute before:left-0 before:top-2 before:bottom-2 before:w-1 before:bg-hoyos-red before:rounded-r-full' : 'text-zinc-400 hover:text-white hover:bg-white/[0.04] hover:border-white/[0.06] border-transparent')]) }}
>
    <div class="flex items-center gap-3 min-w-0">
        @if(isset($icon))
            <div class="shrink-0 transition-colors {{ $active ? 'text-hoyos-red' : 'text-admin-muted group-hover:text-zinc-300' }}">
                {{ $icon }}
            </div>
        @endif
        <span class="truncate">{{ $slot->isEmpty() ? $title : $slot }}</span>
    </div>

    <div class="flex items-center gap-1.5 shrink-0 ml-2">
        @if($badge !== null)
            <x-ui.badge :variant="$badgeVariant" size="xs">
                {{ $badge }}
            </x-ui.badge>
        @endif

        @if($external)
            <span class="text-[10px] font-mono text-zinc-500 group-hover:text-white transition-colors">↗</span>
        @endif
    </div>
</a>
