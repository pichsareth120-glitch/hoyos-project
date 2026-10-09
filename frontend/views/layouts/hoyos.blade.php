<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title . ' | HOYOS' : 'HOYOS — Gen Z Streetwear, Y2K & Goth Fashion' }}</title>
    <meta name="description" content="HOYOS is a bold Cambodian streetwear brand blending Y2K, goth, and dark edgy aesthetics with subtle demon-inspired elements.">

    <!-- Fonts (including Noto Sans Khmer for bilingual safety) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@300;400;500;600;700;800;900&family=Noto+Sans+Khmer:wght@400;500;600;700&family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

    <!-- Scripts & Styles -->
    @vite(['css/app.css', 'js/app.js'])
</head>
<body class="bg-[#080103] text-white font-sans antialiased min-h-screen flex flex-col justify-between selection:bg-hoyos-red selection:text-white relative">

    <!-- Atmospheric Crimson Noir Depth Glows (fixed background) -->
    <div class="fixed inset-0 pointer-events-none -z-10 overflow-hidden" aria-hidden="true">
        <!-- Top-left crimson glow -->
        <div class="absolute -top-32 -left-32 w-[540px] h-[540px] rounded-full bg-[#FF002E]/[0.12] blur-[120px] mix-blend-screen"></div>
        <!-- Right-center deep crimson aura -->
        <div class="absolute top-1/3 -right-40 w-[600px] h-[600px] rounded-full bg-[#E00029]/[0.08] blur-[140px] mix-blend-screen"></div>
        <!-- Bottom-left ambient ruby glow -->
        <div class="absolute -bottom-40 left-1/4 w-[500px] h-[500px] rounded-full bg-[#FF002E]/[0.07] blur-[130px] mix-blend-screen"></div>
    </div>

    <!-- Global Toast Notification Container (Sonner/Shadcn style) -->
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
        class="fixed top-20 right-5 z-50 pointer-events-none"
    >
        <div 
            x-show="show" 
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            class="pointer-events-auto px-4 py-3 rounded-2xl glass-elevated border border-white/15 shadow-glass-elevated flex items-center gap-3 text-xs sm:text-sm text-white"
        >
            <span 
                class="w-2 h-2 rounded-full shadow-sm"
                :class="type === 'error' ? 'bg-[#FF002E] shadow-[0_0_8px_#FF002E]' : 'bg-emerald-400 shadow-[0_0_8px_#34D399]'"
            ></span>
            <span x-text="message" class="font-medium text-white"></span>
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
                <div class="p-4 rounded-2xl glass border border-emerald-500/40 text-emerald-200 text-xs sm:text-sm flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        {{ session('success') }}
                    </span>
                    <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-white p-1">✕</button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <div class="p-4 rounded-2xl glass border border-hoyos-red/60 text-red-200 text-xs sm:text-sm flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-hoyos-red"></span>
                        {{ session('error') }}
                    </span>
                    <button onclick="this.parentElement.remove()" class="text-hoyos-red hover:text-white p-1">✕</button>
                </div>
            </div>
        @endif

        @if(session('info'))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
                <div class="p-4 rounded-2xl glass border border-white/10 text-zinc-300 text-xs sm:text-sm flex items-center justify-between">
                    <span>{{ session('info') }}</span>
                    <button onclick="this.parentElement.remove()" class="text-zinc-400 hover:text-white p-1">✕</button>
                </div>
            </div>
        @endif

        {{ $slot }}
    </main>

    <!-- Footer -->
    <x-footer />

</body>
</html>
