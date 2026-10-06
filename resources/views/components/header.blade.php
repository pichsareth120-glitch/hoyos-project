<header 
    x-data="{ 
        mobileMenuOpen: false, 
        cartCount: 0,
        profileMenuOpen: false
    }"
    @cart-count-updated.window="cartCount = $event.detail.count"
    class="sticky top-0 z-50 w-full bg-hoyos-bg/80 backdrop-blur-xl border-b border-hoyos-border/50 transition-all duration-300"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <!-- Left: Mobile Hamburger & Logo -->
        <div class="flex items-center gap-3 sm:gap-4">
            <button 
                @click="mobileMenuOpen = true"
                class="lg:hidden p-2 rounded-lg text-hoyos-muted hover:text-white hover:bg-hoyos-card transition-colors"
                aria-label="Open navigation menu"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <a href="{{ route('home') }}" class="group flex items-center gap-2" title="HOYOS Home">
                <img 
                    src="{{ asset('images/hoyos/hoyos-wordmark.svg') }}" 
                    alt="HOYOS" 
                    class="w-[120px] h-auto object-contain group-hover:drop-shadow-[0_0_15px_rgba(255,0,46,0.85)] transition-all"
                />
            </a>
        </div>

        <!-- Center: Figma Style Tabs [ Y2K | GOTH | STREETWEAR ] -->
        <nav class="hidden lg:flex items-center p-1 rounded-full bg-hoyos-card/80 border border-hoyos-border shadow-inner">
            <a 
                href="{{ route('category.show', 'y2k') }}" 
                class="px-5 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider transition-all {{ request()->is('category/y2k*') ? 'bg-hoyos-red text-white shadow-crimson-subtle' : 'text-hoyos-muted hover:text-white hover:bg-hoyos-surface' }}"
            >
                Y2K
            </a>
            <a 
                href="{{ route('category.show', 'goth') }}" 
                class="px-5 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider transition-all {{ request()->is('category/goth*') ? 'bg-hoyos-red text-white shadow-crimson-subtle' : 'text-hoyos-muted hover:text-white hover:bg-hoyos-surface' }}"
            >
                GOTH
            </a>
            <a 
                href="{{ route('category.show', 'streetwear') }}" 
                class="px-5 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider transition-all {{ request()->is('category/streetwear*') ? 'bg-hoyos-red text-white shadow-crimson-subtle' : 'text-hoyos-muted hover:text-white hover:bg-hoyos-surface' }}"
            >
                STREETWEAR
            </a>
        </nav>

        <!-- Right: Actions (Track Order, Cart, Login / Sign up) -->
        <div class="flex items-center gap-2 sm:gap-3">
            <!-- Order Lookup Quick Link -->
            <a 
                href="{{ route('orders.lookup') }}" 
                class="hidden md:flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-hoyos-border hover:border-hoyos-borderBright text-xs text-hoyos-muted hover:text-white transition-colors"
                title="Track any order"
            >
                <svg class="w-3.5 h-3.5 text-hoyos-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Track Order
            </a>

            <!-- Shopping Bag Icon Button -->
            <button 
                @click="$dispatch('open-cart')"
                class="relative p-2 sm:p-2.5 rounded-full bg-hoyos-card/90 border border-hoyos-border hover:border-hoyos-red/60 text-white transition-all hover:shadow-crimson-subtle"
                aria-label="Open Shopping Bag"
            >
                <svg class="w-5 h-5 text-hoyos-muted hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <span 
                    x-show="cartCount > 0"
                    x-text="cartCount"
                    class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-hoyos-red text-white text-[10px] font-black flex items-center justify-center shadow-[0_0_8px_#FF002E]"
                ></span>
            </button>

            <!-- Authenticated User Profile or Restored Login / Sign up Buttons -->
            @auth
                <div class="relative" @click.away="profileMenuOpen = false">
                    <button 
                        @click="profileMenuOpen = !profileMenuOpen"
                        class="relative p-2 sm:p-2.5 rounded-full bg-hoyos-card/90 border border-hoyos-border hover:border-hoyos-borderBright text-white transition-all flex items-center justify-center"
                        aria-label="User Profile"
                    >
                        <svg class="w-5 h-5 text-hoyos-muted hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="absolute bottom-1 right-1 w-2 h-2 rounded-full bg-emerald-400 ring-2 ring-black"></span>
                    </button>

                    <!-- Dropdown Menu -->
                    <div 
                        x-show="profileMenuOpen"
                        x-cloak
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-3 w-56 rounded-xl bg-hoyos-surface border border-hoyos-border shadow-2xl py-2 z-50 divide-y divide-hoyos-border"
                    >
                        <div class="px-4 py-2.5">
                            <p class="text-xs text-hoyos-muted uppercase tracking-wider">Signed in as</p>
                            <p class="text-sm font-bold text-white truncate">{{ Auth::user()->name }}</p>
                        </div>
                        <div class="py-1">
                            <a href="{{ route('account.index', ['tab' => 'personal']) }}" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-hoyos-text hover:bg-hoyos-card hover:text-hoyos-red transition-colors">
                                <svg class="w-4 h-4 text-hoyos-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Account Dashboard
                            </a>
                            <a href="{{ route('account.index', ['tab' => 'orders']) }}" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-hoyos-text hover:bg-hoyos-card hover:text-hoyos-red transition-colors">
                                <svg class="w-4 h-4 text-hoyos-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                My Orders & Tracking
                            </a>
                            <a href="{{ route('account.index', ['tab' => 'addresses']) }}" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-hoyos-text hover:bg-hoyos-card hover:text-hoyos-red transition-colors">
                                <svg class="w-4 h-4 text-hoyos-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                Saved Addresses
                            </a>
                            @if(Auth::user()->isAdmin())
                                <a href="{{ route('admin.shipments.index') }}" class="flex items-center gap-2 px-4 py-2 text-xs font-semibold text-amber-400 hover:bg-hoyos-card transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    Admin J&T Fulfillment
                                </a>
                            @endif
                        </div>
                        <div class="py-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-xs font-semibold text-hoyos-dim hover:text-white hover:bg-hoyos-card transition-colors">
                                    Log Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                <!-- Restored Login & Sign up buttons next to cart icon -->
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <a 
                        href="{{ route('login') }}" 
                        class="px-2.5 sm:px-3.5 py-1.5 text-[11px] sm:text-xs font-bold uppercase tracking-wider text-hoyos-muted hover:text-white transition-colors"
                    >
                        Login
                    </a>
                    <a 
                        href="{{ route('register') }}" 
                        class="px-3 sm:px-4 py-1.5 rounded-full bg-hoyos-red hover:bg-hoyos-redHover text-white text-[11px] sm:text-xs font-bold uppercase tracking-wider transition-all shadow-crimson-subtle hover:shadow-[0_0_15px_rgba(255,0,46,0.6)]"
                    >
                        Sign up
                    </a>
                </div>
            @endauth
        </div>
    </div>

    <!-- Mobile Navigation Drawer Teleported to Body -->
    <template x-teleport="body">
        <div 
            x-show="mobileMenuOpen" 
            x-cloak
            class="fixed inset-0 z-50 lg:hidden"
        >
            <!-- Backdrop -->
            <div 
                class="fixed inset-0 bg-black/85 backdrop-blur-md" 
                @click="mobileMenuOpen = false"
                x-show="mobileMenuOpen"
                x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            ></div>

            <!-- Off-canvas panel with opaque dark luxury background -->
            <div 
                class="fixed inset-y-0 left-0 max-w-xs w-full bg-[#0D0204] border-r border-hoyos-border p-6 flex flex-col justify-between shadow-2xl z-50"
                x-show="mobileMenuOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
            >
                <div class="space-y-6">
                    <div class="flex items-center justify-between pb-4 border-b border-hoyos-border">
                        <img 
                            src="{{ asset('images/hoyos/hoyos-wordmark.svg') }}" 
                            alt="HOYOS" 
                            class="w-[110px] h-auto object-contain"
                        />
                        <button @click="mobileMenuOpen = false" class="p-2 text-hoyos-muted hover:text-white" aria-label="Close menu">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="space-y-2">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-hoyos-dim">Style Drops</p>
                        <a href="{{ route('category.show', 'y2k') }}" @click="mobileMenuOpen = false" class="block px-4 py-2.5 rounded-lg text-sm font-bold text-white hover:bg-hoyos-card hover:text-hoyos-red transition-colors">Y2K Collection</a>
                        <a href="{{ route('category.show', 'goth') }}" @click="mobileMenuOpen = false" class="block px-4 py-2.5 rounded-lg text-sm font-bold text-white hover:bg-hoyos-card hover:text-hoyos-red transition-colors">GOTH Collection</a>
                        <a href="{{ route('category.show', 'streetwear') }}" @click="mobileMenuOpen = false" class="block px-4 py-2.5 rounded-lg text-sm font-bold text-white hover:bg-hoyos-card hover:text-hoyos-red transition-colors">STREETWEAR Collection</a>
                    </div>
                    <div class="space-y-2 pt-4 border-t border-hoyos-border">
                        <a href="{{ route('about') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 rounded-lg text-xs font-semibold text-hoyos-muted hover:text-white transition-colors">About Us</a>
                        <a href="{{ route('orders.lookup') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 rounded-lg text-xs font-semibold text-hoyos-muted hover:text-white transition-colors">Track Order</a>
                    </div>
                </div>
                <div class="pt-6 border-t border-hoyos-border">
                    @auth
                        <a href="{{ route('account.index') }}" @click="mobileMenuOpen = false" class="block w-full py-2.5 text-center rounded-full bg-hoyos-red text-white text-xs font-bold uppercase tracking-wider shadow-crimson-subtle">Account Hub</a>
                    @else
                        <div class="grid grid-cols-2 gap-3">
                            <a href="{{ route('login') }}" @click="mobileMenuOpen = false" class="py-2.5 text-center rounded-full border border-hoyos-border hover:border-hoyos-red text-white text-xs font-bold uppercase tracking-wider transition-colors">Login</a>
                            <a href="{{ route('register') }}" @click="mobileMenuOpen = false" class="py-2.5 text-center rounded-full bg-hoyos-red hover:bg-hoyos-redHover text-white text-xs font-bold uppercase tracking-wider shadow-crimson-subtle transition-all">Sign up</a>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </template>
</header>
