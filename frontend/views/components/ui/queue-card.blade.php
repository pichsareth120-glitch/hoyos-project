@props([
    'title',
    'description',
    'count' => 0,
    'badge' => null,
    'link' => '#',
    'urgent' => false,
])

@php
    $isActive = (int)$count > 0;
    $badgeVariant = $isActive ? 'danger' : 'neutral';
@endphp

<div 
    class="rounded-2xl transition-all duration-200 group flex flex-col justify-between relative overflow-hidden focus-ring p-5 sm:p-6 {{ $isActive ? 'bg-[#FF002E]/[0.08] border border-[#FF002E]/45 shadow-[0_0_24px_rgba(255,0,46,0.18)] glass-hover hover:-translate-y-1' : 'glass-subtle glass-hover' }}"
>
    @if($isActive)
        <!-- Ambient Crimson Glow for Active Queues -->
        <div class="absolute -top-12 -right-12 w-28 h-28 rounded-full bg-hoyos-red/15 blur-2xl pointer-events-none"></div>
    @endif

    <div class="relative z-10 space-y-3.5">
        <!-- Badge & Counter Header -->
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-1.5">
                @if($isActive)
                    <span class="w-2 h-2 rounded-full bg-hoyos-red animate-pulse shadow-[0_0_8px_#FF002E]"></span>
                @endif
                <x-ui.badge :variant="$badgeVariant">
                    {{ $badge }}
                </x-ui.badge>
            </div>
            <div class="tabular-nums font-mono font-bold tracking-tight text-2xl {{ $isActive ? 'text-white drop-shadow-[0_0_12px_rgba(255,0,46,0.5)]' : 'text-zinc-400' }}">
                {{ $count }}
            </div>
        </div>

        <!-- Title & Subtitle Description -->
        <div class="space-y-1.5">
            <h3 class="text-xs font-mono font-bold {{ $isActive ? 'text-white group-hover:text-hoyos-red' : 'text-zinc-200 group-hover:text-white' }} transition-colors">
                {{ $title }}
            </h3>
            <p class="text-xs text-zinc-300 line-clamp-2 leading-relaxed font-sans">
                {{ $description }}
            </p>
        </div>
    </div>

    <!-- Footer: Status State & Action Link -->
    <div class="relative z-10 pt-4 mt-4 border-t {{ $isActive ? 'border-hoyos-red/25' : 'border-white/[0.08]' }} flex items-center justify-between text-xs transition-colors">
        <span class="text-[11px] font-mono uppercase tracking-wider {{ $isActive ? 'text-hoyos-red font-semibold' : 'text-zinc-300 font-semibold' }}">
            {{ $isActive ? 'Action Needed' : 'Idle' }}
        </span>

        @if($isActive)
            <a 
                href="{{ $link }}" 
                class="inline-flex items-center gap-1.5 text-white font-sans font-medium hover:text-white group-hover:translate-x-1 transition-all duration-200 focus-ring rounded-lg px-2.5 py-1 bg-white/[0.06] hover:bg-white/[0.12] border border-white/10"
            >
                <span>View Queue</span>
                <span class="text-hoyos-red" aria-hidden="true">&rarr;</span>
            </a>
        @else
            <a 
                href="{{ $link }}" 
                class="inline-flex items-center gap-1 text-zinc-400 font-sans font-medium hover:text-zinc-200 group-hover:translate-x-0.5 transition-colors focus-ring rounded px-1"
            >
                <span>View Queue</span>
                <span class="text-zinc-500 group-hover:text-zinc-300" aria-hidden="true">&rarr;</span>
            </a>
        @endif
    </div>
</div>
