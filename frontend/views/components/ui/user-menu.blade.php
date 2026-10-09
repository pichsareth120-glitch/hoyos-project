<div class="relative" x-data="{ profileOpen: false }" @click.away="profileOpen = false" @keydown.escape="profileOpen = false">
    <button 
        @click="profileOpen = !profileOpen" 
        class="flex items-center gap-2.5 p-1 sm:px-2.5 sm:py-1 rounded-xl bg-white/[0.02] hover:bg-white/[0.06] border border-white/[0.08] hover:border-white/[0.14] transition-all text-left focus-ring"
        aria-haspopup="true"
        :aria-expanded="profileOpen"
    >
        <div class="w-8 h-8 rounded-full bg-hoyos-red/20 border border-hoyos-red/40 flex items-center justify-center text-xs font-mono font-bold text-white shadow-[0_0_10px_rgba(255,0,46,0.3)]">
            {{ strtoupper(substr(Auth::user()?->name ?? 'A', 0, 1)) }}
        </div>
        <div class="hidden sm:block leading-tight pr-1">
            <div class="text-xs font-bold text-white">
                {{ Auth::user()?->name ?? 'Administrator' }}
            </div>
            <div class="text-[9px] font-mono text-zinc-400 uppercase tracking-wider">
                {{ Auth::user()?->email ?? 'admin@hoyos.com' }}
            </div>
        </div>
        <svg class="w-3.5 h-3.5 text-zinc-500 hidden sm:block transition-transform duration-150" :class="profileOpen ? 'rotate-180 text-white' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <!-- Dropdown Menu (Glass Card with shadcn DropdownMenu styling) -->
    <div 
        x-show="profileOpen" 
        x-cloak 
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="transform opacity-0 scale-95 -translate-y-1"
        x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="transform opacity-0 scale-95 -translate-y-1"
        class="absolute right-0 mt-2 w-60 rounded-2xl bg-[#120407]/98 border border-white/[0.12] shadow-[0_20px_45px_-10px_rgba(0,0,0,0.85),inset_0_1px_0_0_rgba(255,255,255,0.08)] py-2 z-50 text-xs font-mono"
    >
        <div class="px-4 py-2.5 border-b border-white/[0.08] text-zinc-400 text-[10px]">
            SIGNED IN AS <div class="text-white font-bold truncate mt-0.5">{{ Auth::user()?->email }}</div>
        </div>
        
        <div class="p-1 space-y-0.5">
            <a 
                href="{{ route('admin.dashboard') }}" 
                class="flex items-center gap-2 px-3 py-2 rounded-xl text-zinc-300 hover:text-white hover:bg-white/[0.05] transition-colors"
            >
                <svg class="w-3.5 h-3.5 text-hoyos-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span>Executive Dashboard</span>
            </a>
            <a 
                href="{{ route('home') }}" 
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center justify-between px-3 py-2 rounded-xl text-zinc-300 hover:text-white hover:bg-white/[0.05] transition-colors"
            >
                <span class="flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    <span>View Storefront</span>
                </span>
                <span class="text-[10px] text-zinc-500">↗</span>
            </a>
            <a 
                href="{{ url('/track-order') }}" 
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center justify-between px-3 py-2 rounded-xl text-zinc-300 hover:text-white hover:bg-white/[0.05] transition-colors"
            >
                <span class="flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span>Customer Tracker</span>
                </span>
                <span class="text-[10px] text-zinc-500">↗</span>
            </a>
        </div>

        <div class="border-t border-white/[0.08] my-1"></div>

        <div class="p-1">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button 
                    type="submit" 
                    class="w-full text-left px-3 py-2 rounded-xl text-hoyos-red hover:bg-hoyos-red/15 hover:text-white transition-all flex items-center justify-between font-bold cursor-pointer"
                >
                    <span>Sign Out</span>
                    <span>→</span>
                </button>
            </form>
        </div>
    </div>
</div>
