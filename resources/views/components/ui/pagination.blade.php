@props([
    'paginator',
])

@if(isset($paginator))
    <div {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-mono text-zinc-400']) }}>
        <div class="text-zinc-400 text-xs">
            Showing <span class="text-zinc-200 font-semibold tabular-nums">{{ $paginator->firstItem() ?? 0 }}</span> to <span class="text-zinc-200 font-semibold tabular-nums">{{ $paginator->lastItem() ?? 0 }}</span> of <span class="text-zinc-200 font-semibold tabular-nums">{{ $paginator->total() ?? 0 }}</span> records
        </div>

        @if($paginator->hasPages())
            <div class="flex items-center gap-1.5">
                {{-- Previous Page Link --}}
                @if($paginator->onFirstPage())
                    <span class="inline-flex items-center justify-center max-sm:min-h-[40px] max-sm:min-w-[40px] px-2.5 py-1 text-xs rounded-lg border border-white/[0.05] bg-white/[0.01] text-zinc-400 opacity-60 cursor-not-allowed select-none">
                        &larr; Prev
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex items-center justify-center max-sm:min-h-[40px] max-sm:min-w-[40px] px-2.5 py-1 text-xs rounded-lg border border-white/[0.1] bg-white/[0.03] text-zinc-300 hover:text-white hover:bg-white/[0.08] hover:border-white/[0.18] transition-all">
                        &larr; Prev
                    </a>
                @endif

                {{-- Current page status --}}
                <span class="inline-flex items-center justify-center max-sm:min-h-[40px] max-sm:min-w-[40px] px-2.5 py-1 text-xs rounded-lg bg-hoyos-red/15 border border-hoyos-red/35 text-white font-bold tabular-nums">
                    {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
                </span>

                {{-- Next Page Link --}}
                @if($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex items-center justify-center max-sm:min-h-[40px] max-sm:min-w-[40px] px-2.5 py-1 text-xs rounded-lg border border-white/[0.1] bg-white/[0.03] text-zinc-300 hover:text-white hover:bg-white/[0.08] hover:border-white/[0.18] transition-all">
                        Next &rarr;
                    </a>
                @else
                    <span class="inline-flex items-center justify-center max-sm:min-h-[40px] max-sm:min-w-[40px] px-2.5 py-1 text-xs rounded-lg border border-white/[0.05] bg-white/[0.01] text-zinc-400 opacity-60 cursor-not-allowed select-none">
                        Next &rarr;
                    </span>
                @endif
            </div>
        @endif
    </div>
@endif
