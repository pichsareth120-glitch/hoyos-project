@props([
    'sticky' => true,
])

@php
    $stickyClasses = $sticky ? 'sticky top-16 z-30' : '';
@endphp

<div {{ $attributes->merge(['class' => "{$stickyClasses} bg-[#0e0407]/[0.96] backdrop-blur-xl rounded-2xl p-4 sm:p-5 border border-white/[0.12] shadow-[0_12px_36px_rgba(0,0,0,0.85)] transition-all"]) }}>
    {{ $slot }}
</div>
