<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title . ' | HOYOS' : 'HOYOS — Gen Z Streetwear, Y2K & Goth Fashion' }}</title>
    <meta name="description" content="HOYOS is a bold Cambodian streetwear brand blending Y2K, goth, and dark edgy aesthetics with subtle demon-inspired elements.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@300;400;500;600;700;800;900&family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&family=Syne:ital,wght@1,800;1,900&display=swap" rel="stylesheet">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-hoyos-bg text-hoyos-text font-sans antialiased min-h-screen flex flex-col justify-between selection:bg-hoyos-red selection:text-white">

    <!-- Global Toast Notification Container -->
    <div 
        x-data="{ 
            show: false, 
            message: '', 
            type: 'success',
            trigger(msg, t = 'success') {
                this.message = msg;
                this.type = t;
                this.show = true;
                setTimeout(() => { this.show = false; }, 3500);
            }
        }"
        @show-toast.window="trigger($event.detail.message, $event.detail.type || 'success')"
        class="fixed top-24 right-5 z-50 pointer-events-none"
    >
        <div 
            x-show="show" 
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2 scale-90"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-90"
            class="pointer-events-auto px-4 py-3 rounded-xl bg-hoyos-surface border border-hoyos-red/60 shadow-crimson-glow flex items-center gap-3 text-sm text-white"
        >
            <span class="w-2 h-2 rounded-full bg-hoyos-red shadow-[0_0_8px_#FF002E]"></span>
            <span x-text="message" class="font-semibold"></span>
        </div>
    </div>

    <!-- Header Navigation -->
    <x-header />

    <!-- Slide-over Bag Drawer -->
    <x-cart-drawer />

    <!-- Main Content -->
    <main class="flex-grow">
        @if(session('success'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <div class="p-4 rounded-xl bg-emerald-950/60 border border-emerald-500/40 text-emerald-200 text-sm flex items-center justify-between">
                    <span>{{ session('success') }}</span>
                    <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-white">✕</button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <div class="p-4 rounded-xl bg-red-950/60 border border-hoyos-red text-red-200 text-sm flex items-center justify-between">
                    <span>{{ session('error') }}</span>
                    <button onclick="this.parentElement.remove()" class="text-hoyos-red hover:text-white">✕</button>
                </div>
            </div>
        @endif

        @if(session('info'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <div class="p-4 rounded-xl bg-hoyos-card border border-hoyos-border text-hoyos-muted text-sm flex items-center justify-between">
                    <span>{{ session('info') }}</span>
                    <button onclick="this.parentElement.remove()" class="text-hoyos-muted hover:text-white">✕</button>
                </div>
            </div>
        @endif

        {{ $slot }}
    </main>

    <!-- Footer -->
    <x-footer />

</body>
</html>
