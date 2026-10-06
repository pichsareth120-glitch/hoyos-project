<x-hoyos-layout :title="ucwords($collectionName) . ' Lookbook'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Collection Headline -->
        <div class="space-y-4 mb-12">
            <span class="text-xs font-semibold uppercase tracking-[0.04em] text-hoyos-red">Editorial Lookbook</span>
            <h1 class="text-3xl sm:text-5xl font-display text-white tracking-wide">
                {{ ucwords($collectionName) }}
            </h1>
            <p class="text-xs text-hoyos-muted max-w-xl leading-relaxed">
                Curated aesthetic styles and cyberpunk-infused garments designed for Gen Z identity expression.
            </p>
        </div>

        <!-- Collection Product Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-6">
            @foreach($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>

        <!-- Crimson Glow Divider Line -->
        <div class="my-20">
            <div class="crimson-glow-line rounded-full"></div>
        </div>

        <!-- "You Might Also Like" Recommendation Section -->
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
