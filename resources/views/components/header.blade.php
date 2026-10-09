@php
    $initialCart = app(\App\Services\CartService::class)->getCart();
    $initialCartCount = $initialCart->total_quantity;
@endphp

<header 
    x-data="{ 
        mobileMenuOpen: false, 
        cartCount: {{ $initialCartCount }},
        profileMenuOpen: false,
        isScrolled: false,
        init() {
            window.addEventListener('scroll', () => {
                this.isScrolled = window.scrollY > 20;
            });
        }
    }"
    @cart-count-updated.window="cartCount = $event.detail.count"
    class="sticky top-0 z-50 w-full transition-all duration-300"
    :class="isScrolled ? 'bg-[#080103]/85 backdrop-blur-2xl border-b border-white/[0.08] shadow-[0_8px_32px_rgba(0,0,0,0.55)] py-2' : 'bg-[#080103]/40 backdrop-blur-md border-b border-white/[0.04] py-3'"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
        <!-- Left: Mobile Menu Trigger & Logo -->
        <div class="flex items-center gap-3 sm:gap-4">
            <button 
                @click="mobileMenuOpen = true"
                class="lg:hidden p-2 rounded-xl text-white/70 hover:text-white hover:bg-white/[0.06] border border-white/10 transition-colors focus-ring"
                aria-label="Open navigation menu"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <a href="{{ route('home') }}" class="group flex items-center gap-2" title="HOYOS Home">
                <img 
                    src="{{ asset('images/hoyos/hoyos-wordmark.svg') }}" 
                    alt="HOYOS" 
                    class="w-[115px] sm:w-[125px] h-auto object-contain group-hover:drop-shadow-[0_0_16px_rgba(255,0,46,0.85)] transition-all duration-300"
                />
            </a>
        </div>

        <!-- Center: Floating Glass Pill Navbar [ Y2K | GOTH | STREETWEAR ] -->
        <nav class="hidden lg:flex items-center p-1 rounded-full glass border border-white/10 shadow-glass-card">
            <a 
                href="{{ route('category.show', 'y2k') }}" 
                class="px-5 py-1.5 rounded-full text-xs font-mono font-bold uppercase tracking-wider transition-all duration-200 {{ request()->is('category/y2k*') ? 'bg-hoyos-red text-white shadow-crimson-subtle' : 'text-white/60 hover:text-white hover:bg-white/[0.06]' }}"
            >
                Y2K
            </a>
            <a 
                href="{{ route('category.show', 'goth') }}" 
                class="px-5 py-1.5 rounded-full text-xs font-mono font-bold uppercase tracking-wider transition-all duration-200 {{ request()->is('category/goth*') ? 'bg-hoyos-red text-white shadow-crimson-subtle' : 'text-white/60 hover:text-white hover:bg-white/[0.06]' }}"
            >
                GOTH
            </a>
            <a 
                href="{{ route('category.show', 'streetwear') }}" 
                class="px-5 py-1.5 rounded-full text-xs font-mono font-bold uppercase tracking-wider transition-all duration-200 {{ request()->is('category/streetwear*') ? 'bg-hoyos-red text-white shadow-crimson-subtle' : 'text-white/60 hover:text-white hover:bg-white/[0.06]' }}"
            >
                STREETWEAR
            </a>
        </nav>

        <!-- Right: Actions (Track Order, Shopping Bag, Profile / Auth) -->
        <div class="flex items-center gap-2 sm:gap-3">
            <!-- Order Lookup Quick Link -->
            <a 
                href="{{ route('orders.lookup') }}" 
                class="hidden md:flex items-center gap-1.5 px-3.5 py-1.5 rounded-full glass border border-white/10 hover:border-hoyos-red/40 text-xs font-mono text-white/70 hover:text-white transition-all hover:shadow-crimson-subtle"
                title="Track any order with Order ID"
            >
                <svg class="w-3.5 h-3.5 text-hoyos-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Track Order</span>
            </a>

            <!-- Shopping Bag Icon Button -->
            <button 
                @click="$dispatch('open-cart')"
                class="relative p-2.5 rounded-full glass border border-white/10 hover:border-hoyos-red/60 text-white transition-all hover:shadow-crimson-subtle focus-ring"
                aria-label="Open Shopping Bag"
            >
                <svg class="w-5 h-5 text-white/80 hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <span 
                    x-show="cartCount > 0"
                    x-text="cartCount"
                    class="absolute -top-1 -right-1 min-w-[20px] h-5 px-1.5 rounded-full bg-hoyos-red text-white text-[10px] font-mono font-black flex items-center justify-center shadow-[0_0_10px_#FF002E]"
                ></span>
            </button>

            <!-- Authenticated User Profile or Login / Sign up -->
            @auth
                <div class="relative" @click.away="profileMenuOpen = false">
                    <button 
                        @click="profileMenuOpen = !profileMenuOpen"
                        class="relative p-2 rounded-full glass border border-white/10 hover:border-white/20 text-white transition-all flex items-center justify-center focus-ring"
                        aria-label="User Account Menu"
                    >
                        <div class="w-7 h-7 rounded-full bg-white/[0.08] border border-white/10 flex items-center justify-center text-xs font-mono font-bold text-white">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <span class="absolute bottom-1 right-1 w-2.5 h-2.5 rounded-full bg-emerald-400 ring-2 ring-[#080103]"></span>
                    </button>

                    <!-- Elevated Glass Dropdown Menu -->
                    <div 
                        x-show="profileMenuOpen"
                        x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="transform opacity-0 scale-95 translate-y-1"
                        x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="transform opacity-0 scale-95 translate-y-1"
                        class="absolute right-0 mt-3 w-60 rounded-2xl glass-elevated border border-white/15 shadow-glass-elevated py-2 z-50 divide-y divide-white/[0.08]"
                    >
                        <div class="px-4 py-2.5">
                            <p class="text-[10px] font-mono uppercase tracking-widest text-white/50">Signed in as</p>
                            <p class="text-xs font-bold text-white truncate mt-0.5">{{ Auth::user()->name }}</p>
                            <p class="text-[10px] font-mono text-white/50 truncate">{{ Auth::user()->email }}</p>
                        </div>
                        <div class="py-1">
                            <a href="{{ route('account.index', ['tab' => 'personal']) }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-white/80 hover:bg-white/[0.06] hover:text-white transition-colors">
                                <svg class="w-4 h-4 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Personal Details
                            </a>
                            <a href="{{ route('account.index', ['tab' => 'addresses']) }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-white/80 hover:bg-white/[0.06] hover:text-white transition-colors">
                                <svg class="w-4 h-4 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                Saved Addresses
                            </a>
                            <a href="{{ route('account.index', ['tab' => 'orders']) }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-white/80 hover:bg-white/[0.06] hover:text-white transition-colors">
                                <svg class="w-4 h-4 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                My Orders & Tracking
                            </a>
                            @if(Auth::user()->isAdmin())
                                <a href="{{ route('admin.shipments.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-amber-300 hover:bg-amber-400/10 transition-colors">
                                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    Admin Fulfillment
                                </a>
                            @endif
                        </div>
                        <div class="py-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-xs font-medium text-hoyos-red hover:bg-hoyos-red/10 transition-colors">
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                <div class="flex items-center gap-2">
                    <a 
                        href="{{ route('login') }}" 
                        class="px-3 py-1.5 text-xs font-mono font-bold uppercase tracking-wider text-white/70 hover:text-white transition-colors"
                    >
                        Login
                    </a>
                    <a 
                        href="{{ route('register') }}" 
                        class="px-4 py-1.5 rounded-full bg-hoyos-red hover:bg-hoyos-redHover text-white text-xs font-mono font-bold uppercase tracking-wider transition-all shadow-crimson-subtle hover:shadow-[0_0_18px_rgba(255,0,46,0.5)]"
                    >
                        Sign up
                    </a>
                </div>
            @endauth
        </div>
    </div>

    <!-- Mobile Drawer -->
    <template x-teleport="body">
        <div 
            x-show="mobileMenuOpen" 
            x-cloak
            class="fixed inset-0 z-50 lg:hidden"
        >
            <div 
                class="fixed inset-0 bg-black/85 backdrop-blur-md" 
                @click="mobileMenuOpen = false"
                x-show="mobileMenuOpen"
                x-transition:enter="transition-opacity ease-linear duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            ></div>

            <div 
                class="fixed inset-y-0 left-0 max-w-xs w-full glass-elevated border-r border-white/15 p-6 flex flex-col justify-between shadow-2xl z-50"
                x-show="mobileMenuOpen"
                x-transition:enter="transition ease-out duration-250"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
            >
                <div class="space-y-6">
                    <div class="flex items-center justify-between pb-4 border-b border-white/10">
                        <img 
                            src="{{ asset('images/hoyos/hoyos-wordmark.svg') }}" 
                            alt="HOYOS" 
                            class="w-[110px] h-auto object-contain"
                        />
                        <button @click="mobileMenuOpen = false" class="p-2 text-white/60 hover:text-white" aria-label="Close menu">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="space-y-2">
                        <p class="text-[10px] font-mono font-bold uppercase tracking-widest text-white/40">Collections</p>
                        <a href="{{ route('category.show', 'y2k') }}" @click="mobileMenuOpen = false" class="block px-4 py-2.5 rounded-xl text-xs font-mono font-bold text-white hover:bg-white/[0.06] hover:text-hoyos-red transition-colors">Y2K Collection</a>
                        <a href="{{ route('category.show', 'goth') }}" @click="mobileMenuOpen = false" class="block px-4 py-2.5 rounded-xl text-xs font-mono font-bold text-white hover:bg-white/[0.06] hover:text-hoyos-red transition-colors">GOTH Collection</a>
                        <a href="{{ route('category.show', 'streetwear') }}" @click="mobileMenuOpen = false" class="block px-4 py-2.5 rounded-xl text-xs font-mono font-bold text-white hover:bg-white/[0.06] hover:text-hoyos-red transition-colors">STREETWEAR Collection</a>
                    </div>
                    <div class="space-y-2 pt-4 border-t border-white/10">
                        <a href="{{ route('about') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 rounded-xl text-xs font-medium text-white/70 hover:text-white transition-colors">About Us</a>
                        <a href="{{ route('orders.lookup') }}" @click="mobileMenuOpen = false" class="block px-4 py-2 rounded-xl text-xs font-medium text-white/70 hover:text-white transition-colors">Track Order</a>
                    </div>
                </div>
                <div class="pt-6 border-t border-white/10">
                    @auth
                        <a href="{{ route('account.index') }}" @click="mobileMenuOpen = false" class="block w-full py-2.5 text-center rounded-xl bg-hoyos-red text-white text-xs font-mono font-bold uppercase tracking-wider shadow-crimson-subtle">My Account Hub</a>
                    @else
                        <div class="grid grid-cols-2 gap-3">
                            <a href="{{ route('login') }}" @click="mobileMenuOpen = false" class="py-2.5 text-center rounded-xl glass border border-white/15 text-white text-xs font-mono font-bold uppercase tracking-wider transition-colors">Login</a>
                            <a href="{{ route('register') }}" @click="mobileMenuOpen = false" class="py-2.5 text-center rounded-xl bg-hoyos-red hover:bg-hoyos-redHover text-white text-xs font-mono font-bold uppercase tracking-wider shadow-crimson-subtle transition-all">Sign up</a>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </template>
</header>
