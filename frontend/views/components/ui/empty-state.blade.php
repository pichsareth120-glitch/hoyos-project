@props([
    'title' => 'No records found',
    'description' => null,
    'actionText' => null,
    'actionHref' => null,
])

<div {{ $attributes->merge(['class' => 'py-8 px-6 text-center flex flex-col items-center justify-center space-y-3 rounded-xl border border-dashed border-white/[0.08] bg-white/[0.01]']) }}>
    @if(isset($icon))
        <div class="w-10 h-10 rounded-full bg-white/[0.03] border border-white/[0.06] flex items-center justify-center text-admin-muted">
            {{ $icon }}
        </div>
    @endif

    <div class="space-y-1">
        <h4 class="text-xs font-mono font-semibold uppercase tracking-wider text-zinc-300">
            {{ $title }}
        </h4>
        @if($description)
            <p class="text-xs text-zinc-400 max-w-sm font-sans leading-relaxed">
                {{ $description }}
            </p>
        @endif
    </div>

    @if($actionText && $actionHref)
        <div class="pt-2">
            <x-ui.button :href="$actionHref" variant="outline" size="xs">
                {{ $actionText }}
            </x-ui.button>
        </div>
    @endif
</div>
