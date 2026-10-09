@props([
    'href' => null,
])

@php
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} 
    @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => "group flex items-center justify-between px-4 py-3 sm:px-5 sm:py-3.5 hover:bg-white/[0.03] transition-colors duration-150 border-b border-white/[0.05] last:border-b-0 " . ($href ? 'cursor-pointer' : '')]) }}
>
    {{ $slot }}
</{{ $tag }}>
