@props([
    'name' => 'confirmModalOpen',
    'title' => 'Confirm Action',
    'description' => null,
    'action' => null,
    'actionDynamic' => null,
    'method' => 'POST',
    'confirmText' => 'Confirm',
    'cancelText' => 'Cancel',
    'variant' => 'danger', // 'danger', 'destructive', 'primary', 'neutral'
    'customFooter' => false,
])

<x-ui.modal
    :name="$name"
    :title="$title"
    :variant="$variant"
    {{ $attributes }}
>
    @if($description)
        <p class="text-xs text-zinc-300 font-sans leading-relaxed">
            {{ $description }}
        </p>
    @endif

    @if($action || $actionDynamic)
        <form method="{{ $method === 'GET' ? 'GET' : 'POST' }}" @if($action) action="{{ $action }}" @endif @if($actionDynamic) :action="{{ $actionDynamic }}" @endif class="space-y-4">
            @csrf
            @if(!in_array($method, ['GET', 'POST']))
                @method($method)
            @endif

            {{ $slot }}

            @if(!$customFooter)
                <div class="flex items-center justify-end gap-3 pt-2">
                    <x-ui.button type="button" @click="{{ $name }} = false" variant="ghost" size="sm" class="font-sans">
                        {{ $cancelText }}
                    </x-ui.button>
                    <x-ui.button type="submit" :variant="$variant" size="sm" class="font-sans">
                        {{ $confirmText }}
                    </x-ui.button>
                </div>
            @endif
        </form>
    @else
        {{ $slot }}

        @if(!$customFooter)
            <div class="flex items-center justify-end gap-3 pt-2">
                <x-ui.button type="button" @click="{{ $name }} = false" variant="ghost" size="sm" class="font-sans">
                    {{ $cancelText }}
                </x-ui.button>
                @if(isset($actions))
                    {{ $actions }}
                @endif
            </div>
        @endif
    @endif
</x-ui.modal>
