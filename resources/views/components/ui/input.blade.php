@props([
    'type' => 'text',
    'label' => null,
    'error' => null,
    'helper' => null,
    'disabled' => false,
    'required' => false,
    'id' => null,
    'name' => null,
    'icon' => null,
])

@php
    $inputId = $id ?? $name ?? 'input-' . uniqid();
    $isPassword = $type === 'password';
@endphp

<div class="space-y-1.5 w-full" @if($isPassword) x-data="{ show: false }" @endif>
    @if($label)
        <label for="{{ $inputId }}" class="block text-xs font-mono font-bold uppercase tracking-wider text-white">
            {{ $label }}
            @if($required)
                <span class="text-hoyos-red">*</span>
            @endif
        </label>
    @endif

    <div class="relative rounded-xl">
        @if($icon)
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-white/40">
                {!! $icon !!}
            </div>
        @endif

        <input 
            @if($isPassword)
                :type="show ? 'text' : 'password'"
            @else
                type="{{ $type }}"
            @endif
            id="{{ $inputId }}"
            name="{{ $name }}"
            @disabled($disabled)
            @if($required) required @endif
            {{ $attributes->merge([
                'class' => 'w-full glass-input py-2.5 text-xs text-white placeholder:text-white/40 rounded-xl transition-all duration-150 focus-ring ' . 
                ($icon ? 'pl-10 pr-4 ' : 'px-4 ') . 
                ($error ? 'border-hoyos-red/80 focus:border-hoyos-red focus:ring-hoyos-red/40 ' : 'border-white/[0.12] focus:border-hoyos-red focus:ring-hoyos-red/30 ') . 
                ($isPassword ? 'pr-11' : '')
            ]) }}
        />

        @if($isPassword)
            <button 
                type="button" 
                @click="show = !show" 
                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-white/50 hover:text-white transition-colors focus:outline-none"
                tabindex="-1"
                :aria-label="show ? 'Hide password' : 'Show password'"
            >
                <!-- Eye icon (when hidden) -->
                <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <!-- Eye off icon (when visible) -->
                <svg x-show="show" x-cloak class="w-4 h-4 text-hoyos-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                </svg>
            </button>
        @endif
    </div>

    @if($error)
        <p class="text-[11px] text-hoyos-red font-medium flex items-center gap-1.5 mt-1" role="alert">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ $error }}</span>
        </p>
    @elseif($helper)
        <p class="text-[11px] text-white/50 mt-1">{{ $helper }}</p>
    @endif
</div>
