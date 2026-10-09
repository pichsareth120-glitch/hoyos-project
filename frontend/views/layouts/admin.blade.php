<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $rawPageTitle = View::hasSection('title') ? View::getSection('title') : ($title ?? null);
        $pageTitle = $rawPageTitle ? html_entity_decode($rawPageTitle, ENT_QUOTES, 'UTF-8') : null;
    @endphp

    <title>{{ $pageTitle ? $pageTitle . ' | HOYOS Backoffice' : 'HOYOS Backoffice — Admin Control Center' }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@300;400;500;600;700;800;900&family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&family=Syne:ital,wght@1,800;1,900&display=swap" rel="stylesheet">

    <!-- Scripts & Styles -->
    @vite(['css/app.css', 'js/app.js'])
</head>
<body class="bg-[#070405] text-zinc-100 font-sans antialiased min-h-screen selection:bg-hoyos-red selection:text-white relative overflow-x-hidden">

    <!-- Ambient Dark Glassmorphism Fixed Radial Glows Behind Everything -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden" style="z-index: -1;" aria-hidden="true">
        <!-- Left Sidebar HOYOS Red Glow (reduced by 25% to 15%, faded toward bottom of sidebar) -->
        <div class="absolute -top-24 -left-28 w-[500px] h-[750px] rounded-full bg-gradient-to-b from-[#FF002E]/15 via-[#FF002E]/[0.06] to-transparent blur-[140px]"></div>
        <!-- Center-Top Red Glow behind KPI row (~700px, 11% opacity) -->
        <div class="absolute -top-28 left-[45%] -translate-x-1/2 w-[700px] h-[700px] rounded-full bg-[#FF002E]/[0.11] blur-[150px]"></div>
        <!-- Right-Middle Deeper Maroon Glow behind chart & feed (~600px, 14% opacity) -->
        <div class="absolute top-[28%] -right-24 w-[600px] h-[600px] rounded-full bg-[#8B0018]/14 blur-[140px]"></div>
        <!-- Bottom-Center Red Glow behind queue grid (~700px, 9% opacity) -->
        <div class="absolute -bottom-44 left-[55%] -translate-x-1/2 w-[700px] h-[700px] rounded-full bg-[#FF002E]/[0.09] blur-[160px]"></div>
        <!-- Very Faint Grid Overlay (2-3% opacity) -->
        <div class="absolute inset-0 bg-[radial-gradient(rgba(255,255,255,0.025)_1px,transparent_1px)] [background-size:24px_24px] opacity-75"></div>
    </div>

    <div 
        x-data="{ 
            sidebarMobileOpen: false, 
            sidebarCollapsed: false,
            toggleSidebarCollapse() {
                this.sidebarCollapsed = !this.sidebarCollapsed;
                try { localStorage.setItem('hoyos_sidebar_collapsed', this.sidebarCollapsed ? '1' : '0'); } catch(e) {}
            },
            init() {
                try {
                    const stored = localStorage.getItem('hoyos_sidebar_collapsed');
                    if (stored !== null) this.sidebarCollapsed = stored === '1';
                } catch(e) {}
            }
        }" 
        class="relative z-10 min-h-screen flex text-zinc-100"
    >
        <!-- Mobile Sidebar Backdrop Overlay -->
        <div 
            x-show="sidebarMobileOpen" 
            x-cloak 
            x-transition:enter="transition-opacity ease-linear duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="sidebarMobileOpen = false" 
            class="fixed inset-0 bg-black/80 backdrop-blur-md z-40 lg:hidden"
            aria-hidden="true"
        ></div>

        <!-- Left Sidebar (Permanent on Desktop, Drawer on Mobile) -->
        <aside 
            :class="{
                '-translate-x-full lg:translate-x-0': !sidebarMobileOpen,
                'translate-x-0': sidebarMobileOpen,
                'w-64': !sidebarCollapsed,
                'w-20': sidebarCollapsed
            }"
            class="fixed inset-y-0 left-0 lg:sticky lg:top-0 h-screen z-50 flex-shrink-0 glass-strong border-r border-white/10 flex flex-col justify-between overflow-y-auto overflow-x-hidden shadow-[0_8px_32px_rgba(0,0,0,0.5)] transition-all duration-250 ease-in-out"
        >
            <div class="flex flex-col h-full">
                <!-- Brand Header -->
                <div :class="sidebarCollapsed ? 'justify-center px-2' : 'justify-between px-4'" class="h-16 flex items-center border-b border-white/[0.08] shrink-0">
                    <a href="{{ route('admin.dashboard') }}" x-show="!sidebarCollapsed" class="flex items-center gap-2.5 group overflow-hidden focus-ring rounded-lg min-h-[40px] py-1 px-1">
                        <span class="w-2.5 h-2.5 rounded-full bg-hoyos-red shadow-[0_0_10px_#FF002E] group-hover:scale-125 transition-transform shrink-0"></span>
                        <div class="flex items-center gap-1.5 transition-opacity duration-200">
                            <span class="font-display text-2xl tracking-wider text-white leading-none">HOYOS</span>
                            <span class="px-1.5 py-0.5 rounded text-[8px] font-mono font-bold uppercase tracking-widest bg-hoyos-red/20 text-hoyos-red border border-hoyos-red/35">
                                HQ
                            </span>
                        </div>
                    </a>

                    <!-- Mobile Close Button -->
                    <button 
                        @click="sidebarMobileOpen = false" 
                        class="lg:hidden p-1.5 rounded-lg text-zinc-400 hover:text-white hover:bg-white/[0.05] focus-ring"
                        aria-label="Close navigation sidebar"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Desktop Collapse / Expand Toggle Button -->
                    <button 
                        @click="toggleSidebarCollapse()"
                        class="hidden lg:flex items-center justify-center w-8 h-8 rounded-lg text-zinc-400 hover:text-white hover:bg-white/[0.05] border border-transparent hover:border-white/[0.08] transition-all focus-ring"
                        :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                        aria-label="Toggle sidebar collapse state"
                    >
                        <svg class="w-4 h-4 transition-transform duration-200" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                        </svg>
                    </button>
                </div>

                <!-- Navigation Links (Scrollable) -->
                <div class="px-2.5 py-5 space-y-6 flex-1 overflow-y-auto">
                    <!-- SIDEBAR GROUP: MANAGEMENT -->
                    <div>
                        <div x-show="!sidebarCollapsed" x-cloak class="px-3 mb-2 text-[10px] font-mono uppercase tracking-widest text-admin-muted font-bold flex items-center justify-between">
                            <span>Management</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-white/[0.1]"></span>
                        </div>
                        <nav class="space-y-1">
                            <!-- Dashboard -->
                            <a 
                                href="{{ route('admin.dashboard') }}" 
                                :class="sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2.5'"
                                class="group relative flex items-center rounded-xl text-xs font-semibold transition-all duration-150 border focus-ring {{ request()->routeIs('admin.dashboard') ? 'glass-nav-active text-white border-hoyos-red/40 shadow-[0_0_18px_rgba(255,0,46,0.25)] before:absolute before:left-0 before:top-2 before:bottom-2 before:w-1 before:bg-hoyos-red before:rounded-r-full before:shadow-[0_0_8px_#FF002E]' : 'text-zinc-400 hover:text-white hover:bg-white/[0.04] hover:border-white/[0.06] border-transparent' }}"
                                :title="sidebarCollapsed ? 'Dashboard' : ''"
                            >
                                <div class="flex items-center gap-3 min-w-0" :class="sidebarCollapsed ? 'justify-center' : ''">
                                    <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('admin.dashboard') ? 'text-hoyos-red' : 'text-zinc-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Dashboard</span>
                                </div>
                                <span x-show="sidebarCollapsed" class="absolute left-full ml-3 px-2.5 py-1 text-[11px] font-mono text-zinc-200 bg-[#16060c]/98 border border-white/[0.12] rounded-lg shadow-xl whitespace-nowrap z-50 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                    Dashboard
                                </span>
                            </a>

                            <!-- Orders & Confirm -->
                            @php
                                $pendingOrdersCount = \App\Models\Order::whereIn('order_status', ['pending', 'confirmed'])->where('payment_status', '!=', 'cancelled')->count();
                            @endphp
                            <a 
                                href="{{ route('admin.orders.index') }}" 
                                :class="sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2.5'"
                                class="group relative flex items-center rounded-xl text-xs font-semibold transition-all duration-150 border focus-ring {{ request()->routeIs('admin.orders.*') ? 'glass-nav-active text-white border-hoyos-red/40 shadow-[0_0_18px_rgba(255,0,46,0.25)] before:absolute before:left-0 before:top-2 before:bottom-2 before:w-1 before:bg-hoyos-red before:rounded-r-full before:shadow-[0_0_8px_#FF002E]' : 'text-zinc-400 hover:text-white hover:bg-white/[0.04] hover:border-white/[0.06] border-transparent' }}"
                                :title="sidebarCollapsed ? 'Orders & Confirm' : ''"
                            >
                                <div class="flex items-center gap-3 min-w-0" :class="sidebarCollapsed ? 'justify-center' : ''">
                                    <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('admin.orders.*') ? 'text-hoyos-red' : 'text-zinc-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Orders & Confirm</span>
                                </div>
                                @if($pendingOrdersCount > 0)
                                    <span :class="sidebarCollapsed ? 'absolute -top-1 -right-1 px-1.5 py-0.2 text-[9px]' : 'px-2 py-0.5 text-[10px]'" class="rounded-full font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/35 shrink-0">
                                        {{ $pendingOrdersCount }}
                                    </span>
                                @endif
                                <span x-show="sidebarCollapsed" class="absolute left-full ml-3 px-2.5 py-1 text-[11px] font-mono text-zinc-200 bg-[#16060c]/98 border border-white/[0.12] rounded-lg shadow-xl whitespace-nowrap z-50 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                    Orders & Confirm ({{ $pendingOrdersCount }})
                                </span>
                            </a>

                            <!-- Invoices & Receipts -->
                            <a 
                                href="{{ route('admin.invoices.index') }}" 
                                :class="sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2.5'"
                                class="group relative flex items-center rounded-xl text-xs font-semibold transition-all duration-150 border focus-ring {{ request()->routeIs('admin.invoices.*') ? 'glass-nav-active text-white border-hoyos-red/40 shadow-[0_0_18px_rgba(255,0,46,0.25)] before:absolute before:left-0 before:top-2 before:bottom-2 before:w-1 before:bg-hoyos-red before:rounded-r-full before:shadow-[0_0_8px_#FF002E]' : 'text-zinc-400 hover:text-white hover:bg-white/[0.04] hover:border-white/[0.06] border-transparent' }}"
                                :title="sidebarCollapsed ? 'Invoices & Receipts' : ''"
                            >
                                <div class="flex items-center gap-3 min-w-0" :class="sidebarCollapsed ? 'justify-center' : ''">
                                    <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('admin.invoices.*') ? 'text-hoyos-red' : 'text-zinc-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Invoices & Receipts</span>
                                </div>
                                <span x-show="sidebarCollapsed" class="absolute left-full ml-3 px-2.5 py-1 text-[11px] font-mono text-zinc-200 bg-[#16060c]/98 border border-white/[0.12] rounded-lg shadow-xl whitespace-nowrap z-50 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                    Invoices & Receipts
                                </span>
                            </a>

                            <!-- Products (Collapsible Alpine Submenu with accessible flyout) -->
                            <div 
                                x-data="{ 
                                    open: {{ (request()->routeIs('admin.products.*') || request()->routeIs('admin.inventory.*')) ? 'true' : 'false' }},
                                    flyoutOpen: false 
                                }" 
                                @mouseenter="if (sidebarCollapsed) flyoutOpen = true"
                                @mouseleave="if (sidebarCollapsed) flyoutOpen = false"
                                @focusout="if (sidebarCollapsed && !$el.contains($event.relatedTarget)) flyoutOpen = false"
                                @keydown.escape.window="if (flyoutOpen) { flyoutOpen = false; $el.querySelector('button[aria-label=\'Products\']')?.focus(); }"
                                class="relative space-y-1"
                            >
                                <button 
                                    type="button"
                                    @click="if (sidebarCollapsed) { flyoutOpen = !flyoutOpen; } else { open = !open; }"
                                    :aria-expanded="sidebarCollapsed ? (flyoutOpen ? 'true' : 'false') : (open ? 'true' : 'false')"
                                    :aria-controls="sidebarCollapsed ? 'products-flyout-submenu' : 'products-submenu'"
                                    :class="sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2.5'"
                                    class="w-full flex items-center rounded-xl text-xs font-semibold transition-all duration-150 border focus-ring {{ (request()->routeIs('admin.products.*') || request()->routeIs('admin.inventory.*')) ? 'text-white bg-white/[0.05] border-white/[0.1]' : 'text-zinc-400 hover:text-white hover:bg-white/[0.04] border-transparent' }}"
                                    :title="sidebarCollapsed ? 'Products' : ''"
                                    aria-label="Products"
                                >
                                    <div class="flex items-center gap-3 min-w-0" :class="sidebarCollapsed ? 'justify-center' : ''">
                                        <svg class="w-4 h-4 shrink-0 transition-colors {{ (request()->routeIs('admin.products.*') || request()->routeIs('admin.inventory.*')) ? 'text-hoyos-red' : 'text-zinc-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                        <span x-show="!sidebarCollapsed" x-cloak class="truncate">Products</span>
                                    </div>
                                    <svg 
                                        x-show="!sidebarCollapsed" 
                                        x-cloak
                                        class="w-3.5 h-3.5 transition-transform duration-200 text-admin-muted"
                                        :class="open ? 'rotate-180 text-white' : ''"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div 
                                    x-show="open && !sidebarCollapsed" 
                                    x-cloak 
                                    id="products-submenu"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    class="mt-1 pl-6 pr-1 space-y-1 border-l border-white/[0.08] ml-4"
                                >
                                    <a 
                                        href="{{ route('admin.products.index') }}" 
                                        class="flex items-center px-3 py-1.5 rounded-lg text-xs font-medium transition-all {{ request()->routeIs('admin.products.index') ? 'text-hoyos-red font-bold bg-hoyos-red/10 border border-hoyos-red/30' : 'text-zinc-400 hover:text-white hover:bg-white/[0.04]' }}"
                                    >
                                        <span>All Products</span>
                                    </a>
                                    <a 
                                        href="{{ route('admin.products.create') }}" 
                                        class="flex items-center px-3 py-1.5 rounded-lg text-xs font-medium transition-all {{ request()->routeIs('admin.products.create') ? 'text-hoyos-red font-bold bg-hoyos-red/10 border border-hoyos-red/30' : 'text-zinc-400 hover:text-white hover:bg-white/[0.04]' }}"
                                    >
                                        <span>Add Product</span>
                                    </a>
                                    <a 
                                        href="{{ route('admin.inventory.index') }}" 
                                        class="flex items-center px-3 py-1.5 rounded-lg text-xs font-medium transition-all {{ request()->routeIs('admin.inventory.*') ? 'text-hoyos-red font-bold bg-hoyos-red/10 border border-hoyos-red/30' : 'text-zinc-400 hover:text-white hover:bg-white/[0.04]' }}"
                                    >
                                        <span>Inventory / Stock</span>
                                    </a>
                                </div>
                                <!-- Collapsed Flyout Menu -->
                                <div 
                                    x-show="sidebarCollapsed && flyoutOpen" 
                                    x-cloak 
                                    id="products-flyout-submenu"
                                    class="absolute left-full top-0 ml-3 py-2 px-1 w-44 bg-[#16060c]/98 border border-white/[0.12] rounded-xl shadow-2xl z-50 space-y-1"
                                >
                                    <div class="px-3 py-1 text-[10px] font-mono uppercase tracking-wider text-admin-muted font-bold border-b border-white/[0.06] mb-1">Products</div>
                                    <a 
                                        href="{{ route('admin.products.index') }}" 
                                        class="flex items-center px-3 py-1.5 rounded-lg text-xs font-medium transition-all {{ request()->routeIs('admin.products.index') ? 'text-hoyos-red font-bold bg-hoyos-red/10' : 'text-zinc-300 hover:text-white hover:bg-white/[0.06]' }}"
                                    >
                                        <span>All Products</span>
                                    </a>
                                    <a 
                                        href="{{ route('admin.products.create') }}" 
                                        class="flex items-center px-3 py-1.5 rounded-lg text-xs font-medium transition-all {{ request()->routeIs('admin.products.create') ? 'text-hoyos-red font-bold bg-hoyos-red/10' : 'text-zinc-300 hover:text-white hover:bg-white/[0.06]' }}"
                                    >
                                        <span>Add Product</span>
                                    </a>
                                    <a 
                                        href="{{ route('admin.inventory.index') }}" 
                                        class="flex items-center px-3 py-1.5 rounded-lg text-xs font-medium transition-all {{ request()->routeIs('admin.inventory.*') ? 'text-hoyos-red font-bold bg-hoyos-red/10' : 'text-zinc-300 hover:text-white hover:bg-white/[0.06]' }}"
                                    >
                                        <span>Inventory / Stock</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Shipments -->
                            <a 
                                href="{{ route('admin.shipments.index') }}" 
                                :class="sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2.5'"
                                class="group relative flex items-center rounded-xl text-xs font-semibold transition-all duration-150 border focus-ring {{ request()->routeIs('admin.shipments.*') ? 'glass-nav-active text-white border-hoyos-red/40 shadow-[0_0_18px_rgba(255,0,46,0.25)] before:absolute before:left-0 before:top-2 before:bottom-2 before:w-1 before:bg-hoyos-red before:rounded-r-full before:shadow-[0_0_8px_#FF002E]' : 'text-zinc-400 hover:text-white hover:bg-white/[0.04] hover:border-white/[0.06] border-transparent' }}"
                                :title="sidebarCollapsed ? 'Shipments' : ''"
                            >
                                <div class="flex items-center gap-3 min-w-0" :class="sidebarCollapsed ? 'justify-center' : ''">
                                    <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('admin.shipments.*') ? 'text-hoyos-red' : 'text-zinc-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Shipments</span>
                                </div>
                                <span x-show="sidebarCollapsed" class="absolute left-full ml-3 px-2.5 py-1 text-[11px] font-mono text-zinc-200 bg-[#16060c]/98 border border-white/[0.12] rounded-lg shadow-xl whitespace-nowrap z-50 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                    Shipments
                                </span>
                            </a>

                            <!-- Payments Audit -->
                            <a 
                                href="{{ route('admin.payments.index') }}" 
                                :class="sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2.5'"
                                class="group relative flex items-center rounded-xl text-xs font-semibold transition-all duration-150 border focus-ring {{ request()->routeIs('admin.payments.*') ? 'glass-nav-active text-white border-hoyos-red/40 shadow-[0_0_18px_rgba(255,0,46,0.25)] before:absolute before:left-0 before:top-2 before:bottom-2 before:w-1 before:bg-hoyos-red before:rounded-r-full before:shadow-[0_0_8px_#FF002E]' : 'text-zinc-400 hover:text-white hover:bg-white/[0.04] hover:border-white/[0.06] border-transparent' }}"
                                :title="sidebarCollapsed ? 'Payments Audit' : ''"
                            >
                                <div class="flex items-center gap-3 min-w-0" :class="sidebarCollapsed ? 'justify-center' : ''">
                                    <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('admin.payments.*') ? 'text-hoyos-red' : 'text-zinc-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Payments Audit</span>
                                </div>
                                <span x-show="sidebarCollapsed" class="absolute left-full ml-3 px-2.5 py-1 text-[11px] font-mono text-zinc-200 bg-[#16060c]/98 border border-white/[0.12] rounded-lg shadow-xl whitespace-nowrap z-50 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                    Payments Audit
                                </span>
                            </a>

                            <!-- Customers -->
                            <a 
                                href="{{ route('admin.customers.index') }}" 
                                :class="sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2.5'"
                                class="group relative flex items-center rounded-xl text-xs font-semibold transition-all duration-150 border focus-ring {{ request()->routeIs('admin.customers.*') ? 'glass-nav-active text-white border-hoyos-red/40 shadow-[0_0_18px_rgba(255,0,46,0.25)] before:absolute before:left-0 before:top-2 before:bottom-2 before:w-1 before:bg-hoyos-red before:rounded-r-full before:shadow-[0_0_8px_#FF002E]' : 'text-zinc-400 hover:text-white hover:bg-white/[0.04] hover:border-white/[0.06] border-transparent' }}"
                                :title="sidebarCollapsed ? 'Customers' : ''"
                            >
                                <div class="flex items-center gap-3 min-w-0" :class="sidebarCollapsed ? 'justify-center' : ''">
                                    <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('admin.customers.*') ? 'text-hoyos-red' : 'text-zinc-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Customers</span>
                                </div>
                                <span x-show="sidebarCollapsed" class="absolute left-full ml-3 px-2.5 py-1 text-[11px] font-mono text-zinc-200 bg-[#16060c]/98 border border-white/[0.12] rounded-lg shadow-xl whitespace-nowrap z-50 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                    Customers
                                </span>
                            </a>

                            <!-- Reports & Analytics -->
                            <a 
                                href="{{ route('admin.reports.sales') }}" 
                                :class="sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2.5'"
                                class="group relative flex items-center rounded-xl text-xs font-semibold transition-all duration-150 border focus-ring {{ request()->routeIs('admin.reports.*') ? 'glass-nav-active text-white border-hoyos-red/40 shadow-[0_0_18px_rgba(255,0,46,0.25)] before:absolute before:left-0 before:top-2 before:bottom-2 before:w-1 before:bg-hoyos-red before:rounded-r-full before:shadow-[0_0_8px_#FF002E]' : 'text-zinc-400 hover:text-white hover:bg-white/[0.04] hover:border-white/[0.06] border-transparent' }}"
                                :title="sidebarCollapsed ? 'Reports & Analytics' : ''"
                            >
                                <div class="flex items-center gap-3 min-w-0" :class="sidebarCollapsed ? 'justify-center' : ''">
                                    <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('admin.reports.*') ? 'text-hoyos-red' : 'text-zinc-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Reports & Analytics</span>
                                </div>
                                <span x-show="sidebarCollapsed" class="absolute left-full ml-3 px-2.5 py-1 text-[11px] font-mono text-zinc-200 bg-[#16060c]/98 border border-white/[0.12] rounded-lg shadow-xl whitespace-nowrap z-50 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                    Reports & Analytics
                                </span>
                            </a>

                            <!-- Audit Logs -->
                            <a 
                                href="{{ route('admin.audit-logs.index') }}" 
                                :class="sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2.5'"
                                class="group relative flex items-center rounded-xl text-xs font-semibold transition-all duration-150 border focus-ring {{ request()->routeIs('admin.audit-logs.*') ? 'glass-nav-active text-white border-hoyos-red/40 shadow-[0_0_18px_rgba(255,0,46,0.25)] before:absolute before:left-0 before:top-2 before:bottom-2 before:w-1 before:bg-hoyos-red before:rounded-r-full before:shadow-[0_0_8px_#FF002E]' : 'text-zinc-400 hover:text-white hover:bg-white/[0.04] hover:border-white/[0.06] border-transparent' }}"
                                :title="sidebarCollapsed ? 'Audit Logs' : ''"
                            >
                                <div class="flex items-center gap-3 min-w-0" :class="sidebarCollapsed ? 'justify-center' : ''">
                                    <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('admin.audit-logs.*') ? 'text-hoyos-red' : 'text-zinc-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Audit Logs</span>
                                </div>
                                <span x-show="sidebarCollapsed" class="absolute left-full ml-3 px-2.5 py-1 text-[11px] font-mono text-zinc-200 bg-[#16060c]/98 border border-white/[0.12] rounded-lg shadow-xl whitespace-nowrap z-50 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                    Audit Logs
                                </span>
                            </a>
                        </nav>
                    </div>

                    <!-- SIDEBAR GROUP: FRONTEND & STORE -->
                    <div>
                        <div x-show="!sidebarCollapsed" x-cloak class="px-3 mb-2 text-[10px] font-mono uppercase tracking-widest text-admin-muted font-bold flex items-center justify-between">
                            <span>Frontend & Store</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-white/[0.1]"></span>
                        </div>
                        <nav class="space-y-1">
                            <!-- View Store -->
                            <a 
                                href="{{ route('home') }}" 
                                target="_blank"
                                rel="noopener noreferrer"
                                :class="sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2.5'"
                                class="group relative flex items-center rounded-xl text-xs font-semibold text-zinc-400 hover:text-white hover:bg-white/[0.04] border border-transparent hover:border-white/[0.06] transition-all focus-ring"
                                :title="sidebarCollapsed ? 'View Storefront' : ''"
                            >
                                <div class="flex items-center gap-3 min-w-0" :class="sidebarCollapsed ? 'justify-center' : ''">
                                    <svg class="w-4 h-4 text-hoyos-red shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">View Storefront</span>
                                </div>
                                <span x-show="!sidebarCollapsed" x-cloak class="text-[10px] font-mono text-zinc-500 group-hover:text-white transition-colors">↗</span>
                                <span x-show="sidebarCollapsed" class="absolute left-full ml-3 px-2.5 py-1 text-[11px] font-mono text-zinc-200 bg-[#16060c]/98 border border-white/[0.12] rounded-lg shadow-xl whitespace-nowrap z-50 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                    View Storefront ↗
                                </span>
                            </a>

                            <!-- Customer Tracker -->
                            <a 
                                href="{{ url('/track-order') }}" 
                                target="_blank"
                                rel="noopener noreferrer"
                                :class="sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2.5'"
                                class="group relative flex items-center rounded-xl text-xs font-semibold text-zinc-400 hover:text-white hover:bg-white/[0.04] border border-transparent hover:border-white/[0.06] transition-all focus-ring"
                                :title="sidebarCollapsed ? 'Customer Tracker' : ''"
                            >
                                <div class="flex items-center gap-3 min-w-0" :class="sidebarCollapsed ? 'justify-center' : ''">
                                    <svg class="w-4 h-4 text-sky-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-cloak class="truncate">Customer Tracker</span>
                                </div>
                                <span x-show="!sidebarCollapsed" x-cloak class="text-[10px] font-mono text-zinc-500 group-hover:text-white transition-colors">↗</span>
                                <span x-show="sidebarCollapsed" class="absolute left-full ml-3 px-2.5 py-1 text-[11px] font-mono text-zinc-200 bg-[#16060c]/98 border border-white/[0.12] rounded-lg shadow-xl whitespace-nowrap z-50 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                    Customer Tracker ↗
                                </span>
                            </a>
                        </nav>
                    </div>
                </div>

                <!-- Sidebar Footer with Glass Button -->
                <div class="p-3 border-t border-white/[0.08] shrink-0">
                    <a 
                        href="{{ route('home') }}" 
                        target="_blank"
                        rel="noopener noreferrer"
                        :class="sidebarCollapsed ? 'justify-center px-2 py-2.5' : 'justify-between px-3 py-2.5'"
                        class="bg-white/[0.03] hover:bg-white/[0.06] flex items-center rounded-xl min-h-[40px] text-xs font-mono font-medium text-zinc-300 hover:text-white border border-white/[0.08] hover:border-white/[0.16] transition-all focus-ring shadow-sm"
                        :title="sidebarCollapsed ? 'Open Storefront' : ''"
                    >
                        <span class="flex items-center gap-2.5 min-w-0" :class="sidebarCollapsed ? 'justify-center' : ''">
                            <svg class="w-3.5 h-3.5 text-hoyos-red shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            <span x-show="!sidebarCollapsed" x-cloak class="truncate">Live Store</span>
                        </span>
                        <span x-show="!sidebarCollapsed" x-cloak class="text-[10px] text-zinc-500">↗</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content Column -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Navbar with Breadcrumbs & User Profile Dropdown -->
            <header class="h-16 glass-strong !bg-[#0e0407]/[0.92] backdrop-blur-xl border-b border-white/10 flex items-center justify-between px-4 sm:px-6 sticky top-0 z-40 shadow-[0_4px_24px_rgba(0,0,0,0.4)]">
                <div class="flex items-center gap-3">
                    <!-- Mobile Hamburger Toggle Button -->
                    <button 
                        @click="sidebarMobileOpen = !sidebarMobileOpen"
                        type="button"
                        class="lg:hidden p-2 rounded-xl text-zinc-400 hover:text-white hover:bg-white/[0.05] border border-white/[0.08] focus-ring"
                        aria-label="Open mobile navigation"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <!-- Breadcrumb Trail -->
                    <nav class="flex items-center gap-2 font-mono text-xs" aria-label="Breadcrumb">
                        <span class="text-admin-muted uppercase tracking-wider hidden sm:inline">CONSOLE</span>
                        <span class="text-zinc-600 hidden sm:inline" aria-hidden="true">//</span>
                        <h2 class="font-bold uppercase tracking-wider text-white truncate">
                            {{ $pageTitle ?? 'Executive Dashboard' }}
                        </h2>
                    </nav>
                </div>

                <!-- Right Header Actions & Admin Profile Dropdown -->
                <div class="flex items-center gap-2.5 sm:gap-4">
                    <!-- Quick Action Link: Open Website / View Store -->
                    <a 
                        href="{{ route('home') }}" 
                        target="_blank"
                        rel="noopener noreferrer"
                        class="hidden md:inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-white/[0.03] hover:bg-white/[0.08] text-xs font-mono font-semibold text-zinc-300 hover:text-white border border-white/[0.08] hover:border-white/[0.18] transition-all duration-150 shadow-sm focus-ring"
                    >
                        <svg class="w-3.5 h-3.5 text-hoyos-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        <span>View Store</span>
                        <span class="text-[10px] text-zinc-500">↗</span>
                    </a>

                    <!-- Admin Profile Dropdown (Shadcn styled UserMenu) -->
                    <x-ui.user-menu />
                </div>
            </header>

            <!-- Main Body Content Area -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 animate-fade-in">
                <!-- Session Flash Messages (Glass Banners) -->
                @if(session('success'))
                    <div class="mb-6 p-4 rounded-2xl bg-emerald-950/70 border border-emerald-500/40 text-emerald-200 text-xs sm:text-sm flex items-center justify-between shadow-[0_10px_25px_-5px_rgba(16,185,129,0.15)]">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-lg bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="font-mono">{{ session('success') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-white text-xs px-2 py-1 rounded-lg hover:bg-emerald-500/20 transition-colors" aria-label="Dismiss notification">✕</button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 rounded-2xl bg-red-950/75 border border-hoyos-red/50 text-red-200 text-xs sm:text-sm flex items-center justify-between shadow-[0_10px_25px_-5px_rgba(255,0,46,0.2)]">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-lg bg-hoyos-red/20 border border-hoyos-red/35 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-hoyos-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                            <span class="font-mono">{{ session('error') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-hoyos-red hover:text-white text-xs px-2 py-1 rounded-lg hover:bg-hoyos-red/20 transition-colors" aria-label="Dismiss notification">✕</button>
                    </div>
                @endif

                @if(session('info'))
                    <div class="mb-6 p-4 rounded-2xl bg-zinc-900/90 border border-white/[0.1] text-zinc-300 text-xs sm:text-sm flex items-center justify-between shadow-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-lg bg-sky-500/20 border border-sky-500/30 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="font-mono">{{ session('info') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-zinc-400 hover:text-white text-xs px-2 py-1 rounded-lg hover:bg-white/[0.05] transition-colors" aria-label="Dismiss notification">✕</button>
                    </div>
                @endif

                <x-ui.error-summary />

                @yield('content')
                {{ $slot ?? '' }}
            </main>
        </div>
    </div>

</body>
</html>
