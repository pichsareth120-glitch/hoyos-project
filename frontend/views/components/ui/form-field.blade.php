@props([
    'label' => null,
    'name' => null,
    'required' => false,
    'helper' => null,
    'error' => null,
])

@php
    $fieldName = $name;
    $hasError = !empty($error) || ($fieldName && $errors->has($fieldName));
    $errorMessage = $error ?? ($fieldName && $errors->has($fieldName) ? $errors->first($fieldName) : null);
    $errorId = $fieldName ? \Illuminate\Support\Str::slug(str_replace('.', '-', $fieldName)) . '-error' : 'error-' . uniqid();

    $renderedSlot = (string) $slot;
    if ($hasError && $renderedSlot) {
        $pattern = '/<(input|select|textarea)\b([^>]*)>/i';
        if (preg_match($pattern, $renderedSlot, $matches)) {
            $tag = $matches[1];
            $attrs = $matches[2];

            // 1. Ensure aria-invalid="true"
            if (strpos($attrs, 'aria-invalid') === false) {
                $attrs .= ' aria-invalid="true"';
            } else {
                $attrs = preg_replace('/aria-invalid="[^"]*"/', 'aria-invalid="true"', $attrs);
            }

            // 2. Link with aria-describedby
            if (strpos($attrs, 'aria-describedby') === false) {
                $attrs .= ' aria-describedby="' . $errorId . '"';
            } else {
                $attrs = preg_replace('/aria-describedby="([^"]*)"/', 'aria-describedby="$1 ' . $errorId . '"', $attrs);
            }

            // 3. Inject red border and red focus ring classes
            $errorBorderClasses = '!border-rose-500 !ring-rose-500/40 !focus:border-rose-400 !focus:ring-rose-400 ring-1';
            if (preg_match('/class="([^"]*)"/', $attrs, $classMatch)) {
                $newClass = trim($classMatch[1] . ' ' . $errorBorderClasses);
                $attrs = preg_replace('/class="[^"]*"/', 'class="' . $newClass . '"', $attrs);
            } else {
                $attrs .= ' class="' . $errorBorderClasses . '"';
            }

            $replacement = "<{$tag}{$attrs}>";
            $renderedSlot = preg_replace($pattern, $replacement, $renderedSlot, 1);
        }
    }
@endphp

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="block text-[11px] font-mono uppercase font-semibold tracking-wider text-zinc-300">
            {{ $label }}
            @if($required)
                <span class="text-hoyos-red font-bold">*</span>
            @endif
        </label>
    @endif

    {!! $renderedSlot !!}

    @if($helper)
        <p class="text-[10px] text-zinc-400 font-sans leading-normal">
            {{ $helper }}
        </p>
    @endif

    @if($hasError && $errorMessage)
        <p id="{{ $errorId }}" class="text-[11px] text-rose-300 font-sans leading-tight flex items-center gap-1.5 mt-1" role="alert">
            <svg class="w-3.5 h-3.5 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span>{{ $errorMessage }}</span>
        </p>
    @endif
</div>
