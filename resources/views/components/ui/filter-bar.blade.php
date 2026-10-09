@props([
    'action' => null,
    'method' => 'GET',
    'search' => null,
    'searchPlaceholder' => 'Search records...',
    'searchName' => 'search',
    'resetUrl' => null,
    'hasActiveFilters' => false,
])

<div {{ $attributes->merge(['class' => 'glass-subtle rounded-2xl p-3.5 sm:p-4 border border-white/[0.08] shadow-sm mb-6']) }}>
    <form method="{{ $method }}" @if($action) action="{{ $action }}" @endif class="flex flex-wrap min-[1440px]:flex-nowrap items-center gap-3 w-full">
        @if($search !== false)
            <div class="relative flex-1 min-w-[240px]">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-admin-muted">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </span>
                <input 
                    type="text" 
                    name="{{ $searchName }}" 
                    value="{{ $search ?? request($searchName) }}" 
                    placeholder="{{ $searchPlaceholder }}" 
                    class="w-full pl-9 pr-3.5 py-2 text-xs rounded-xl bg-white/[0.03] border border-white/[0.1] text-zinc-100 placeholder:text-admin-muted focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all font-mono"
                >
            </div>
        @endif

        {{ $slot }}

        <div class="flex items-center gap-2 shrink-0">
            <x-ui.button type="submit" variant="primary" size="sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                <span>Filter</span>
            </x-ui.button>

            @if($hasActiveFilters && $resetUrl)
                <x-ui.button :href="$resetUrl" variant="secondary" size="sm">
                    Reset
                </x-ui.button>
            @endif
        </div>
    </form>
</div>
