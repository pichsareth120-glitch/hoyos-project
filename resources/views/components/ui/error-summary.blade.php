@props([
    'title' => null,
])

@if($errors->any())
    @php
        $count = $errors->count();
        $defaultTitle = "Please correct {$count} " . \Illuminate\Support\Str::plural('issue', $count) . ' before saving:';
    @endphp
    <div 
        id="form-error-summary" 
        {{ $attributes->merge(['class' => 'p-4 sm:p-5 rounded-2xl bg-hoyos-red/10 border border-hoyos-red/30 space-y-2.5 shadow-lg shadow-hoyos-red/5 text-left mb-6']) }}
        role="alert"
        aria-live="assertive"
    >
        <div class="flex items-center gap-2 text-hoyos-red font-mono text-xs font-bold uppercase tracking-wider">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span>{{ $title ?? $defaultTitle }}</span>
        </div>
        <ul class="list-disc list-inside text-xs font-sans text-rose-300 space-y-1">
            @foreach($errors->messages() as $field => $messages)
                @php
                    $fieldId = 'field-' . \Illuminate\Support\Str::slug(str_replace('.', '-', $field));
                    $cleanFieldName = ucfirst(str_replace(['_', '.'], ' ', $field));
                @endphp
                <li>
                    <a href="#{{ $fieldId }}" class="hover:underline font-mono text-[11px] text-white font-medium">
                        {{ $cleanFieldName }}:
                    </a>
                    <span class="text-rose-200">{{ $messages[0] }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
