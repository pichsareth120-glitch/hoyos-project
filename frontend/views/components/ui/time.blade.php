@props([
    'value' => null,
    'format' => 'd M Y, H:i',
    'label' => true,
])

{!! \App\Support\Time::format($value, $format, $label, $attributes->get('class')) !!}
