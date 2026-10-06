<x-hoyos-layout :title="$activeCategory ? $activeCategory->name . ' Collection' : 'All Collections'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Category Title & Hero Tagline -->
        <div class="space-y-4 mb-10">
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-display text-white tracking-wider uppercase">
                @if($activeCategory)
                    {{ ucfirst($activeCategory->name) }} Collection
                @else
                    All Collections & Drops
                @endif
            </h1>
            <p class="text-xs sm:text-sm text-hoyos-muted max-w-xl">
                @if($activeCategory && $activeCategory->description)
                    {{ $activeCategory->description }}
                @else
                    Discover our full wardrobe of dark Y2K rebellion, cyber accents, and oversized gothic silhouettes.
                @endif
            </p>
        </div>

        <!-- Category Selector Pills -->
        <div class="flex items-center gap-2 pb-6 border-b border-hoyos-border overflow-x-auto">
            <a 
                href="{{ route('category.show') }}"
                class="px-5 py-2 rounded-full text-xs font-semibold uppercase tracking-[0.04em] transition-all {{ !$activeCategory ? 'bg-hoyos-red text-white shadow-crimson-subtle' : 'bg-hoyos-card border border-hoyos-border text-hoyos-muted hover:text-white' }}"
            >
                All ({{ $products->total() }})
            </a>
            @foreach($categories as $cat)
                <a 
                    href="{{ route('category.show', $cat->slug) }}"
                    class="px-5 py-2 rounded-full text-xs font-semibold uppercase tracking-[0.04em] transition-all {{ $activeCategory && $activeCategory->id === $cat->id ? 'bg-hoyos-red text-white shadow-crimson-subtle' : 'bg-hoyos-card border border-hoyos-border text-hoyos-muted hover:text-white' }}"
                >
                    {{ $cat->name }}
                </a>
            @endforeach
        </div>

        <!-- Main Product Grid (2 cols <640px, 3 cols tablet, 4 cols desktop) -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-6 mt-8">
            @forelse($products as $product)
                <x-product-card :product="$product" />
            @empty
                <div class="col-span-full py-20 text-center space-y-3">
                    <p class="text-hoyos-muted text-sm uppercase">No drops currently found in this category.</p>
                    <a href="{{ route('category.show') }}" class="text-xs text-hoyos-red font-semibold uppercase tracking-[0.04em] hover:underline">View All Drops</a>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($products->hasPages())
            <div class="mt-12">
                {{ $products->links() }}
            </div>
        @endif

        <!-- Crimson Glow Divider Line -->
        <div class="my-16">
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
