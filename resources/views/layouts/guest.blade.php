<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title . ' | HOYOS' : 'HOYOS — Authentic Y2K & Goth Streetwear' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-hoyos-bg text-hoyos-text font-sans antialiased min-h-screen selection:bg-hoyos-red selection:text-white flex flex-col justify-between">

    <!-- Top mini header for quick back navigation -->
    <div class="px-6 py-4 flex items-center justify-between border-b border-hoyos-border/50">
        <a href="{{ route('home') }}" class="flex items-center gap-2 group">
            <span class="font-display text-2xl tracking-widest text-white uppercase italic group-hover:drop-shadow-[0_0_12px_rgba(255,0,46,0.8)] transition-all">
                HOY<span class="text-hoyos-red">O</span>S
            </span>
        </a>
        <a href="{{ route('home') }}" class="text-xs font-semibold text-hoyos-muted hover:text-white transition-colors flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to store
        </a>
    </div>

    <!-- Main Split-Screen Content -->
    <main class="flex-1 flex items-center justify-center p-4 sm:p-8">
        {{ $slot }}
    </main>

    <!-- Simple Auth Footer -->
    <footer class="py-4 text-center border-t border-hoyos-border/40 text-[11px] text-hoyos-dim">
        © {{ date('Y') }} HOYOS (Cambodia). Your outfit speaks louder than words.
    </footer>

</body>
</html>
