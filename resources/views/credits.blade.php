<x-hoyos-layout title="Image Credits & Photographers">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <!-- Header -->
        <div class="text-center space-y-4 mb-14">
            <span class="text-xs font-semibold uppercase tracking-[0.04em] text-hoyos-red">Brand Archive & Attribution</span>
            <h1 class="text-3xl sm:text-5xl font-display tracking-widest text-white uppercase drop-shadow-[0_0_20px_rgba(255,0,46,0.5)]">
                Image Credits & Sources
            </h1>
            <p class="text-xs sm:text-sm text-hoyos-muted max-w-2xl mx-auto leading-relaxed">
                HOYOS respects creative rights and intellectual property. Visual photography across our catalogue is attributed below.
            </p>
            <div class="w-20 h-1 bg-hoyos-red mx-auto rounded-full shadow-[0_0_12px_#FF002E]"></div>
        </div>

        @if($creditedImages->isEmpty())
            <div class="max-w-md mx-auto text-center py-20 px-6 rounded-2xl bg-hoyos-card border border-hoyos-border space-y-4">
                <div class="w-12 h-12 rounded-full bg-hoyos-darkred/40 border border-hoyos-red/40 flex items-center justify-center mx-auto text-hoyos-red">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-white tracking-[0.04em]">Photographer Credits</h3>
                <p class="text-xs text-hoyos-muted leading-relaxed">
                    Image credits will appear here once product photography has been synchronized.
                </p>
                <a href="{{ route('category.show') }}" class="inline-block px-6 py-2.5 rounded-full bg-hoyos-red hover:bg-hoyos-redHover text-white text-xs font-semibold uppercase tracking-[0.04em] transition-all">
                    Browse All Products
                </a>
            </div>
        @else
            <!-- Credits Table -->
            <div class="overflow-x-auto rounded-2xl border border-hoyos-border bg-hoyos-surface shadow-2xl">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-hoyos-border bg-hoyos-card/90 text-xs font-semibold uppercase tracking-[0.04em] text-hoyos-muted">
                            <th scope="col" class="py-4 px-6 w-28">Thumbnail</th>
                            <th scope="col" class="py-4 px-6">Product</th>
                            <th scope="col" class="py-4 px-6">Source / Credit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-hoyos-border">
                        @foreach($creditedImages as $img)
                            <tr class="hover:bg-hoyos-card/50 transition-colors">
                                <!-- Thumbnail -->
                                <td class="py-4 px-6 align-middle">
                                    <div class="w-16 h-20 rounded-lg overflow-hidden bg-black border border-hoyos-border shadow-md">
                                        <img 
                                            src="{{ asset($img->image_path) }}" 
                                            alt="{{ $img->product?->name ?? 'Product' }}" 
                                            class="w-full h-full object-cover"
                                            loading="lazy"
                                        />
                                    </div>
                                </td>

                                <!-- Product Info -->
                                <td class="py-4 px-6 align-middle">
                                    <div class="space-y-1">
                                        @if($img->product)
                                            <a 
                                                href="{{ route('products.show', $img->product->slug) }}" 
                                                class="text-sm sm:text-base font-semibold text-white hover:text-hoyos-red transition-colors tracking-[0.04em] block"
                                            >
                                                {{ $img->product->name }}
                                            </a>
                                            @if($img->product->category)
                                                <span class="inline-block text-[11px] font-mono uppercase tracking-wider text-hoyos-dim">
                                                    {{ $img->product->category->name }} Collection
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-sm font-semibold text-white">Archived Product</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Source / Credit -->
                                <td class="py-4 px-6 align-middle">
                                    @php
                                        $hasValidSourceUrl = $img->source_url && filter_var($img->source_url, FILTER_VALIDATE_URL) && \Illuminate\Support\Str::startsWith($img->source_url, ['http://', 'https://']);
                                        $isDemoOnly = trim($img->credit) === 'Original brand photo (demo use only)';
                                    @endphp
                                    <div class="space-y-1 text-xs">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-hoyos-red flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            <span class="text-white font-medium">{{ $img->credit ?: 'Original brand photo (demo use only)' }}</span>
                                        </div>
                                        @if(!$isDemoOnly && $hasValidSourceUrl)
                                            <a 
                                                href="{{ $img->source_url }}" 
                                                target="_blank" 
                                                rel="noopener noreferrer" 
                                                class="inline-flex items-center gap-1 text-hoyos-red hover:underline font-mono text-[11px]"
                                            >
                                                <span>Source Link</span>
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-hoyos-layout>
