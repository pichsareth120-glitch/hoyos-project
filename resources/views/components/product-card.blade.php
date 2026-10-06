@props(['product'])

@php
    $primaryImage = $product->primaryImage ? asset($product->primaryImage->image_path) : asset('images/hoyos/0ffd9c02726576625184fe52b38ace6d.jpg');
@endphp

<div 
    x-data="{ 
        sizeSelectorOpen: false, 
        adding: false,
        addVariant(variantId) {
            this.adding = true;
            fetch('{{ route('cart.add') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ variant_id: variantId, quantity: 1 })
            })
            .then(res => res.json())
            .then(data => {
                this.adding = false;
                this.sizeSelectorOpen = false;
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('cart-updated'));
                    window.dispatchEvent(new CustomEvent('open-cart'));
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Added to your bag' } }));
                }
            })
            .catch(err => {
                this.adding = false;
                console.error(err);
            });
        }
    }"
    class="group relative flex flex-col justify-between rounded-xl bg-hoyos-card border border-hoyos-border hover:border-hoyos-borderBright p-3 transition-all duration-300 hover:shadow-crimson-subtle h-full"
>
    <!-- Product Thumbnail -->
    <a href="{{ route('products.show', $product->slug) }}" class="relative block overflow-hidden rounded-lg bg-black aspect-[4/5]">
        <img 
            src="{{ $primaryImage }}" 
            alt="{{ $product->name }}"
            class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-500"
            loading="lazy"
        />
        
        <!-- Category Pill Badge -->
        <span class="absolute top-2.5 left-2.5 px-2.5 py-0.5 rounded-full bg-black/75 backdrop-blur-md border border-white/10 text-[10px] sm:text-[11px] font-semibold uppercase tracking-[0.04em] text-hoyos-muted">
            {{ $product->category->name }}
        </span>

        @if($product->is_new_arrival)
            <span class="absolute top-2.5 right-2.5 px-2.5 py-0.5 rounded-full bg-hoyos-red text-white text-[10px] font-semibold uppercase tracking-[0.04em] shadow-[0_0_8px_#FF002E]">
                NEW
            </span>
        @endif
    </a>

    <!-- Meta Details -->
    <div class="mt-3.5 space-y-1.5 flex-1 flex flex-col justify-between">
        <a href="{{ route('products.show', $product->slug) }}" class="block">
            <h3 class="text-sm sm:text-base font-semibold text-white group-hover:text-hoyos-red transition-colors line-clamp-2 tracking-[0.04em] uppercase min-h-[2.5rem] sm:min-h-[2.75rem] flex items-start">
                {{ $product->name }}
            </h3>
        </a>

        <div class="flex items-center justify-between text-xs pt-1">
            <div class="flex items-center gap-1 text-amber-400 font-mono">
                <span>★</span>
                <span class="font-bold text-white">{{ number_format($product->rating, 1) }}</span>
                <span class="text-hoyos-dim text-[10px]">/ 5</span>
            </div>
            <span class="font-mono font-extrabold text-sm text-white">
                {{ $product->formatted_price }}
            </span>
        </div>
    </div>

    <!-- Add to Bag / Inline Size Selector -->
    <div class="mt-3.5 pt-2 border-t border-hoyos-border/60">
        <!-- Default State Button -->
        <button 
            x-show="!sizeSelectorOpen"
            @click="sizeSelectorOpen = true"
            class="w-full py-2 px-3 rounded-lg border border-hoyos-red/60 hover:bg-hoyos-red hover:text-white text-hoyos-red text-xs font-semibold uppercase tracking-[0.04em] transition-all flex items-center justify-center gap-1.5"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            Add to bag
        </button>

        <!-- Inline Quick Size Selector -->
        <div 
            x-show="sizeSelectorOpen" 
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="space-y-1.5"
            @click.away="sizeSelectorOpen = false"
        >
            <div class="flex items-center justify-between text-[10px] text-hoyos-muted uppercase font-bold tracking-wider">
                <span>Select Size:</span>
                <button @click="sizeSelectorOpen = false" class="text-hoyos-dim hover:text-white">✕</button>
            </div>
            <div class="grid grid-cols-4 gap-1">
                @foreach($product->variants as $variant)
                    <button 
                        @click="addVariant({{ $variant->id }})"
                        :disabled="adding || {{ $variant->stock_quantity <= 0 ? 'true' : 'false' }}"
                        class="py-1 px-1.5 rounded bg-hoyos-surface border border-hoyos-border hover:border-hoyos-red hover:bg-hoyos-darkred/60 text-white text-[11px] font-bold uppercase transition-all {{ $variant->stock_quantity <= 0 ? 'opacity-30 cursor-not-allowed line-through' : '' }}"
                    >
                        {{ $variant->size }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>
