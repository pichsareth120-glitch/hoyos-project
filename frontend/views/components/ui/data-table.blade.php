@props([
    'empty' => false,
    'emptyTitle' => 'No records found',
    'emptyDescription' => 'There are no records matching your current filter criteria.',
    'emptyActionText' => null,
    'emptyActionHref' => null,
    'loading' => false,
    'skeletonRows' => 5,
    'skeletonCols' => 6,
])

<div {{ $attributes->merge(['class' => 'glass rounded-2xl border border-white/[0.08] shadow-sm overflow-hidden flex flex-col']) }}>
    @if(isset($header))
        <div class="p-4 sm:p-5 border-b border-white/[0.08] flex items-center justify-between gap-4">
            {{ $header }}
        </div>
    @endif

    <div class="overflow-x-auto flex-1">
        <table class="w-full text-left border-collapse">
            @if(isset($thead))
                <thead class="sticky top-0 z-10 bg-zinc-950/95 border-b border-white/[0.08] text-[11px] font-mono uppercase tracking-wider text-zinc-400">
                    {{ $thead }}
                </thead>
            @endif

            <tbody class="divide-y divide-white/[0.04] text-xs font-sans">
                @if($loading)
                    @for($r = 0; $r < $skeletonRows; $r++)
                        <tr class="hover:bg-white/[0.02] transition-colors">
                            @for($c = 0; $c < $skeletonCols; $c++)
                                <td class="px-5 py-4">
                                    <div class="h-4 bg-white/[0.05] rounded animate-pulse w-full"></div>
                                </td>
                            @endfor
                        </tr>
                    @endfor
                @elseif($empty)
                    <tr>
                        <td colspan="100" class="p-8 sm:p-12 text-center">
                            @if(isset($emptyState))
                                {{ $emptyState }}
                            @else
                                <x-ui.empty-state
                                    :title="$emptyTitle"
                                    :description="$emptyDescription"
                                    :actionText="$emptyActionText"
                                    :actionHref="$emptyActionHref"
                                />
                            @endif
                        </td>
                    </tr>
                @else
                    {{ $slot }}
                @endif
            </tbody>
        </table>
    </div>

    @if(isset($footer))
        <div class="px-5 py-3 border-t border-white/[0.06] bg-zinc-950/30">
            {{ $footer }}
        </div>
    @endif
</div>
