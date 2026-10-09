<x-hoyos-layout :title="$product->name">
    <div 
        x-data="{
            selectedImage: '{{ $product->primaryImage ? asset($product->primaryImage->image_path) : asset('images/hoyos/0ffd9c02726576625184fe52b38ace6d.jpg') }}',
            selectedAlt: '{{ $product->name }}',
            selectedVariantId: {{ $product->variants->first()?->id ?? 'null' }},
            selectedSize: '{{ $product->variants->first()?->size ?? 'M' }}',
            adding: false,
            added: false,
            addToCart() {
                if (!this.selectedVariantId) return;
                this.adding = true;
                fetch('{{ route('cart.add') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ variant_id: this.selectedVariantId, quantity: 1 })
                })
                .then(res => res.json())
                .then(data => {
                    this.adding = false;
                    this.added = true;
                    setTimeout(() => { this.added = false; }, 2000);
                    if (data.success) {
                        window.dispatchEvent(new CustomEvent('cart-updated'));
                        window.dispatchEvent(new CustomEvent('open-cart'));
                        window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Item added to your shopping bag' } }));
                    }
                })
                .catch(err => {
                    this.adding = false;
                    console.error(err);
                });
            }
        }"
        class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10"
    >
        <!-- Back Navigation Breadcrumb -->
        <div class="mb-8">
            <a href="{{ url()->previous() ?: route('category.show') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-hoyos-muted hover:text-white transition-colors">
                <svg class="w-4 h-4 text-hoyos-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to collection
            </a>
        </div>

        <!-- Main Product Section (2 Columns) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
            <!-- Left: Multi-Angle Thumbnail Gallery & Main Image -->
            <div class="lg:col-span-7 flex flex-col-reverse sm:flex-row gap-4">
                <!-- Thumbnail Strip -->
                <div class="flex sm:flex-col gap-3 shrink-0 overflow-x-auto sm:overflow-y-auto max-h-[620px] scrollbar-thin">
                    @foreach($product->images as $index => $image)
                        @php
                            $framing = $index === 0 ? 'Front View' : ($index === 1 ? 'Torso & Fabric Detail' : 'Full Look & Silhouette');
                            $framingShort = $index === 0 ? 'Front' : ($index === 1 ? 'Detail' : 'Full');
                        @endphp
                        <button 
                            type="button"
                            @click="selectedImage = '{{ asset($image->image_path) }}'; selectedAlt = '{{ $product->name }} — {{ $framing }}'"
                            class="group relative flex-shrink-0 w-20 sm:w-24 aspect-[4/5] rounded-xl overflow-hidden border-2 transition-all bg-black"
                            :class="selectedImage === '{{ asset($image->image_path) }}' ? 'border-hoyos-red shadow-crimson-subtle ring-1 ring-hoyos-red opacity-100' : 'border-hoyos-border hover:border-hoyos-borderBright opacity-70 hover:opacity-100'"
                            aria-label="{{ $product->name }} {{ $framing }}"
                        >
                            <img 
                                src="{{ asset($image->image_path) }}" 
                                alt="{{ $product->name }} — {{ $framing }}" 
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                loading="lazy"
                            />
                            <span class="absolute bottom-1 inset-x-1 text-[9px] font-mono font-bold uppercase tracking-wider text-center py-0.5 rounded bg-black/80 backdrop-blur-sm text-gray-300 border border-white/10">
                                {{ $framingShort }}
                            </span>
                        </button>
                    @endforeach
                </div>

                <!-- Main Display Image -->
                <div class="flex-1 min-w-0 space-y-2">
                    <div class="rounded-2xl overflow-hidden bg-black border border-hoyos-border shadow-2xl aspect-[4/5] relative">
                        <img 
                            src="{{ $product->primaryImage ? asset($product->primaryImage->image_path) : asset('images/hoyos/0ffd9c02726576625184fe52b38ace6d.jpg') }}"
                            :src="selectedImage" 
                            :alt="selectedAlt || '{{ $product->name }}'" 
                            class="w-full h-full object-cover transition-all duration-300"
                        />
                        <div class="absolute top-4 right-4">
                            <span class="px-3 py-1 rounded-full bg-black/75 backdrop-blur-md border border-white/10 text-xs font-mono font-bold uppercase tracking-wider text-hoyos-muted">
                                {{ $product->category->name }}
                            </span>
                        </div>
                    </div>

                    @if($product->primaryImage && $product->primaryImage->credit)
                        @php
                            $hasValidSourceUrl = $product->primaryImage->source_url && filter_var($product->primaryImage->source_url, FILTER_VALIDATE_URL) && \Illuminate\Support\Str::startsWith($product->primaryImage->source_url, ['http://', 'https://']);
                            $isDemoOnly = trim($product->primaryImage->credit) === 'Original brand photo (demo use only)';
                        @endphp
                        <div class="flex items-center justify-between px-1 text-[11px] text-hoyos-dim font-mono">
                            <span>Credit: <strong class="text-hoyos-muted">{{ $product->primaryImage->credit }}</strong></span>
                            @if(!$isDemoOnly && $hasValidSourceUrl)
                                <a href="{{ $product->primaryImage->source_url }}" target="_blank" rel="noopener noreferrer" class="text-hoyos-red hover:underline inline-flex items-center gap-1 font-bold">
                                    <span>Source</span>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right: Product Information & Size Selection -->
            <div class="lg:col-span-5 space-y-8">
                <!-- Title & Price -->
                <div class="space-y-3 pb-6 border-b border-hoyos-border">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-[0.04em] text-hoyos-red">
                            {{ $product->category ? $product->category->name . ' COLLECTION' : ($product->collection_name ?: 'HOYOS Core') }}
                        </span>
                        <div class="flex items-center gap-1 text-amber-400 text-xs font-mono">
                            <span>★</span>
                            <span class="font-bold text-white">{{ number_format($product->rating, 1) }}</span>
                            <span class="text-hoyos-dim">({{ $product->review_count }} reviews)</span>
                        </div>
                    </div>

                    <h1 class="text-3xl sm:text-4xl font-display text-white uppercase tracking-wider">
                        {{ $product->name }}
                    </h1>

                    <div class="flex items-baseline gap-3">
                        <span class="text-2xl sm:text-3xl font-black font-mono text-white">
                            {{ $product->formatted_price }}
                        </span>
                        @if($product->compare_at_price)
                            <span class="text-sm font-mono text-hoyos-dim line-through">
                                ${{ number_format($product->compare_at_price, 2) }}
                            </span>
                        @endif
                        <span class="text-[11px] font-semibold uppercase tracking-[0.04em] text-emerald-400 ml-auto">
                            In Stock
                        </span>
                    </div>
                </div>

                <!-- Select Size (M, L, XL, XXL) -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold uppercase tracking-[0.04em] text-white">
                            Select Size
                        </label>
                        <span class="text-xs font-mono text-hoyos-muted">Selected: <strong class="text-white" x-text="selectedSize"></strong></span>
                    </div>

                    <div class="grid grid-cols-3 sm:grid-cols-6 gap-2.5">
                        @foreach($product->variants->sortBy(fn($v) => \App\Models\ProductVariant::sizeOrderIndex($v->size)) as $variant)
                            <button 
                                type="button"
                                @click="selectedVariantId = {{ $variant->id }}; selectedSize = '{{ $variant->size }}'"
                                :class="selectedVariantId === {{ $variant->id }} ? 'bg-hoyos-red text-white border-hoyos-red shadow-crimson-subtle' : 'bg-hoyos-card border-hoyos-border text-hoyos-muted hover:text-white hover:border-hoyos-borderBright'"
                                class="py-3 px-2 rounded-xl border text-sm font-semibold uppercase tracking-[0.04em] transition-all flex flex-col items-center justify-center gap-1"
                            >
                                <span>{{ $variant->size }}</span>
                                <span class="text-[9px] opacity-70 font-normal">
                                    {{ $variant->stock_quantity > 0 ? $variant->stock_quantity . ' left' : 'Out' }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Add to Bag CTA Button -->
                <div class="space-y-3 pt-4">
                    <button 
                        type="button"
                        id="add-to-bag-button"
                        @click="addToCart()"
                        :disabled="adding || !selectedVariantId"
                        class="w-full py-4 px-6 rounded-full bg-hoyos-darkred/90 hover:bg-hoyos-red text-white font-semibold text-sm uppercase tracking-[0.04em] transition-all duration-300 border border-hoyos-red shadow-crimson-glow hover:shadow-[0_0_30px_rgba(255,0,46,0.8)] flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span x-show="!adding && !added">Add to bag</span>
                        <span x-show="adding" x-cloak>Adding to bag...</span>
                        <span x-show="added" x-cloak>Added to bag! ✓</span>
                    </button>

                    <div class="flex items-center justify-center gap-6 text-[11px] text-hoyos-dim pt-2 font-mono">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-hoyos-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            J&T Express Courier
                        </span>
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-hoyos-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            ABA KHQR & COD
                        </span>
                    </div>
                </div>

                <!-- Description & Size Guide -->
                <div class="pt-6 border-t border-hoyos-border space-y-6">
                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.04em] text-white">
                            Description
                        </h3>
                        <div class="text-xs text-hoyos-muted leading-relaxed whitespace-pre-line">
                            {{ $product->description }}
                        </div>
                    </div>

                    @if($product->size_guide)
                        <div class="space-y-3 p-4 rounded-xl bg-hoyos-card/50 border border-hoyos-border">
                            <h3 class="text-xs font-semibold uppercase tracking-[0.04em] text-hoyos-red flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                Sizing Guide (KG & Height)
                            </h3>
                            <div class="text-xs text-hoyos-muted leading-relaxed whitespace-pre-line font-mono">
                                {{ $product->size_guide }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Crimson Glow Divider Line -->
        <div class="my-20">
            <div class="crimson-glow-line rounded-full"></div>
        </div>

        <!-- "You Might Also Like" Recommendation Section (From Figma) -->
        <div class="space-y-8">
            <div class="text-center space-y-2">
                <h2 class="text-2xl font-display text-white uppercase tracking-wider">
                    You might also like
                </h2>
                <div class="w-12 h-1 bg-hoyos-red mx-auto rounded-full shadow-[0_0_8px_#FF002E]"></div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-6">
                @foreach($recommendations as $recProduct)
                    <x-product-card :product="$recProduct" />
                @endforeach
            </div>
        </div>
    </div>
</x-hoyos-layout>
