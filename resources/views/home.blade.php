<x-hoyos-layout title="Home">
    <!-- Full-Width Hero with HOYOS Artwork Cover Background (min-height ~70vh on mobile, ~85vh on desktop) -->
    <section class="relative w-full min-h-[70vh] md:min-h-[85vh] flex flex-col justify-end items-center text-center overflow-hidden border-b border-hoyos-border">
        <!-- Cover Background Artwork with uncropped responsive mobile contain -->
        <div class="absolute inset-0 w-full h-full bg-[#080002]">
            <img 
                src="{{ asset('images/hoyos/hoyoslogo.png') }}" 
                alt="HOYOS Streetwear Artwork" 
                class="w-full h-full object-contain object-[center_36%] md:object-cover md:object-center select-none pointer-events-none hero-artwork-mask"
            />
            <!-- Top dark vignette to blend with sticky translucent header -->
            <div class="absolute inset-x-0 top-0 h-16 md:h-32 bg-gradient-to-b from-black/90 via-black/40 to-transparent pointer-events-none"></div>

            <!-- Dark gradient fade at the bottom of the hero -->
            <div class="absolute inset-x-0 bottom-0 h-40 sm:h-80 md:h-96 bg-gradient-to-t from-hoyos-bg via-hoyos-bg/95 to-transparent pointer-events-none"></div>
        </div>

        <!-- Overlaid Content on Lower Part of the Hero -->
        <div class="relative z-10 w-full max-w-4xl mx-auto px-4 pb-6 sm:pb-16 md:pb-20 flex flex-col items-center space-y-4 sm:space-y-6">
            <!-- Shop Now & New Arrival Action Buttons -->
            <div class="flex flex-wrap items-center justify-center gap-3 sm:gap-4 pt-1 sm:pt-4">
                <a 
                    href="{{ route('category.show') }}" 
                    class="px-8 sm:px-10 py-3 sm:py-3.5 rounded-full bg-hoyos-red hover:bg-hoyos-redHover text-white text-xs sm:text-sm font-semibold uppercase tracking-[0.04em] transition-all duration-300 shadow-crimson-glow hover:shadow-[0_0_35px_rgba(255,0,46,0.9)] transform hover:-translate-y-0.5 active:translate-y-0"
                >
                    Shop Now
                </a>
                <a 
                    href="{{ route('category.show', ['categorySlug' => 'streetwear']) }}" 
                    class="px-8 sm:px-10 py-3 sm:py-3.5 rounded-full bg-black/60 hover:bg-hoyos-card border border-white/30 hover:border-hoyos-red/70 text-white text-xs sm:text-sm font-semibold uppercase tracking-[0.04em] backdrop-blur-md transition-all duration-300 transform hover:-translate-y-0.5 active:translate-y-0 shadow-lg hover:shadow-crimson-subtle"
                >
                    New Arrival
                </a>
            </div>

            <!-- High-Contrast White Tagline Overlaid on Lower Part of Hero -->
            <p class="text-xs sm:text-base md:text-lg text-white font-semibold max-w-2xl mx-auto leading-relaxed drop-shadow-[0_4px_24px_rgba(0,0,0,1)] tracking-wide px-4 py-2 rounded-xl bg-black/40 backdrop-blur-sm border border-white/10 shadow-2xl">
                HOYOS is a bold fashion brand for Gen Z streetwear, blending Y2K, goth, and dark edgy styles with subtle demon-inspired elements. Choose your outfit—let your look speak louder than words.
            </p>
        </div>
    </section>

    <!-- "Our Collection" Section with Dark-to-Red Gradient and Category Cards Directly Below -->
    <section class="relative w-full py-16 sm:py-24 overflow-hidden bg-gradient-to-b from-hoyos-bg via-[#260309] to-hoyos-bg border-b border-hoyos-border">
        <!-- Ambient atmospheric crimson glow in the background -->
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_60%_at_50%_50%,rgba(255,0,46,0.12),transparent_70%)] pointer-events-none"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Header -->
            <div class="text-center space-y-3 mb-12 sm:mb-16">
                <span class="text-xs font-semibold uppercase tracking-[0.04em] text-hoyos-red">Curated Aesthetics</span>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-display tracking-[0.04em] text-white uppercase">
                    Our Collection
                </h2>
                <div class="w-20 h-1 bg-hoyos-red mx-auto rounded-full shadow-[0_0_12px_#FF002E]"></div>
            </div>

            <!-- Category Cards: Y2K, Goth, Streetwear -->
            @php
                $categoryCards = [
                    [
                        'name' => 'Y2K',
                        'slug' => 'y2k',
                        'tagline' => 'Cyber nostalgia, baggy fits & early 2000s rebellious energy',
                        'image' => asset('images/hoyos/0ffd9c02726576625184fe52b38ace6d.jpg'),
                        'number' => '01',
                    ],
                    [
                        'name' => 'GOTH',
                        'slug' => 'goth',
                        'tagline' => 'Dark gothic edge, raw distressed textures & demon aesthetics',
                        'image' => asset('images/hoyos/43087ed8c434aaf444e0cf52ffb1cd37.jpg'),
                        'number' => '02',
                    ],
                    [
                        'name' => 'STREETWEAR',
                        'slug' => 'streetwear',
                        'tagline' => 'Oversized Gen Z cuts & bold urban statements',
                        'image' => asset('images/hoyos/groupphoto1.jpg'),
                        'number' => '03',
                    ],
                ];
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
                @foreach($categoryCards as $card)
                    <a 
                        href="{{ route('category.show', $card['slug']) }}" 
                        class="group relative rounded-2xl overflow-hidden bg-hoyos-card border border-hoyos-border hover:border-hoyos-red/70 transition-all duration-500 shadow-2xl hover:shadow-[0_0_35px_rgba(255,0,46,0.35)] aspect-[3/4.2] flex flex-col justify-end transform hover:-translate-y-1.5"
                    >
                        <!-- Category Image with smooth zoom -->
                        <img 
                            src="{{ $card['image'] }}" 
                            alt="{{ $card['name'] }} Collection" 
                            class="absolute inset-0 w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-700 ease-out"
                        />

                        <!-- Gradient Overlays for contrast and moody edge -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/95 via-black/40 to-transparent group-hover:from-black transition-colors duration-300"></div>
                        <div class="absolute inset-0 bg-hoyos-red/0 group-hover:bg-hoyos-red/10 transition-colors duration-500 pointer-events-none"></div>

                        <!-- Drop Number Pill Badge -->
                        <div class="absolute top-4 right-4 z-10">
                            <span class="px-3 py-1 rounded-full text-[10px] font-semibold uppercase tracking-[0.04em] bg-black/75 backdrop-blur-md text-white/90 border border-white/10 group-hover:border-hoyos-red/60 group-hover:text-hoyos-red transition-all">
                                {{ $card['number'] }} DROP
                            </span>
                        </div>

                        <!-- Content at Bottom of Card -->
                        <div class="relative z-10 p-5 sm:p-7 space-y-2">
                            <h3 
                                style="font-size: clamp(1.25rem, 2.4vw, 2.25rem); line-height: 1.1;"
                                class="font-display text-white uppercase tracking-[0.04em] group-hover:text-hoyos-red transition-colors duration-300"
                            >
                                {{ $card['name'] }}
                            </h3>
                            <p class="text-xs sm:text-sm text-hoyos-muted group-hover:text-gray-200 transition-colors duration-300 line-clamp-2">
                                {{ $card['tagline'] }}
                            </p>
                            <div class="pt-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.04em] text-hoyos-red group-hover:text-white transition-colors duration-300">
                                <span>Explore Drop</span>
                                <svg class="w-4 h-4 transform group-hover:translate-x-1.5 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Crimson Glow Divider Line -->
    <div class="max-w-5xl mx-auto px-4 my-8">
        <div class="crimson-glow-line rounded-full"></div>
    </div>

    <!-- Editorial Story 1: Neon Phantom Drift -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            <!-- Left Lookbook Image with Angular Wireframe Accent -->
            <div class="lg:col-span-6 relative">
                <!-- Red angular line decorative border -->
                <div class="absolute -top-4 -left-4 w-16 h-16 border-t-2 border-l-2 border-hoyos-red pointer-events-none"></div>
                <div class="relative rounded-2xl overflow-hidden border border-hoyos-border bg-hoyos-card shadow-2xl aspect-[16/10]">
                    <img 
                        src="{{ asset('images/hoyos/neon-phantom-lookbook.png') }}" 
                        alt="Neon Phantom Drift Lookbook" 
                        class="w-full h-full object-cover"
                    />
                </div>
                <div class="absolute -bottom-4 -right-4 w-16 h-16 border-b-2 border-r-2 border-hoyos-red pointer-events-none"></div>
            </div>

            <!-- Right Story Copy -->
            <div class="lg:col-span-6 space-y-6">
                <div class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.04em] text-hoyos-red">New Collection Drop</span>
                    <h3 class="text-2xl sm:text-3xl font-display text-white tracking-[0.04em]">
                        Neon Phantom Drift
                    </h3>
                </div>
                <p class="text-sm text-hoyos-muted leading-relaxed">
                    Inspired by cyberpunk anime like Ghost in the Shell, this outfit blends futuristic Tokyo streetwear with early 2000s rave culture. Metallic textures, neon accents, and slim silhouettes create a digital rebel aesthetic. It feels like a character moving through a glitching city—half human, half code.
                </p>
                <div>
                    <a 
                        href="{{ route('products.collection', 'neon-phantom-drift') }}" 
                        class="inline-flex items-center gap-2 px-8 py-3 rounded-full bg-hoyos-darkred/90 hover:bg-hoyos-red border border-hoyos-red/60 text-white text-xs font-semibold uppercase tracking-[0.04em] transition-all shadow-crimson-subtle"
                    >
                        View Collection
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Crimson Glow Divider Line -->
    <div class="max-w-5xl mx-auto px-4 my-8">
        <div class="crimson-glow-line rounded-full"></div>
    </div>

    <!-- Editorial Story 2: Stone Guardian Core -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            <!-- Left Story Copy -->
            <div class="lg:col-span-6 space-y-6 order-2 lg:order-1">
                <div class="space-y-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.04em] text-hoyos-red">Signature Look</span>
                    <h3 class="text-2xl sm:text-3xl font-display text-white tracking-[0.04em]">
                        Stone Guardian Core
                    </h3>
                </div>
                <p class="text-sm text-hoyos-muted leading-relaxed">
                    Engineered for dark gothic street styling, this architectural armor set combines a structured dark indigo workwear jacket with precision spiderweb geometric contour stitching, bold silver hardware, and flared contour denim trousers. High-density tailoring and dark fantasy aesthetics built for modern urban dominance.
                </p>
                <div>
                    <a 
                        href="{{ route('products.collection', 'stone-guardian-core') }}" 
                        class="inline-flex items-center gap-2 px-8 py-3 rounded-full bg-hoyos-darkred/90 hover:bg-hoyos-red border border-hoyos-red/60 text-white text-xs font-semibold uppercase tracking-[0.04em] transition-all shadow-crimson-subtle"
                    >
                        View Collection
                    </a>
                </div>
            </div>

            <!-- Right Lookbook Image -->
            <div class="lg:col-span-6 relative order-1 lg:order-2">
                <div class="absolute -top-4 -right-4 w-16 h-16 border-t-2 border-r-2 border-hoyos-red pointer-events-none"></div>
                <div class="relative rounded-2xl overflow-hidden border border-hoyos-border bg-hoyos-card shadow-2xl aspect-[16/10]">
                    <img 
                        src="{{ asset('images/hoyos/stone-guardian-lookbook.png') }}" 
                        alt="Stone Guardian Core Lookbook" 
                        class="w-full h-full object-cover"
                    />
                </div>
                <div class="absolute -bottom-4 -left-4 w-16 h-16 border-b-2 border-l-2 border-hoyos-red pointer-events-none"></div>
            </div>
        </div>
    </section>

    <!-- Crimson Glow Divider Line -->
    <div class="max-w-5xl mx-auto px-4 my-8">
        <div class="crimson-glow-line rounded-full"></div>
    </div>

    <!-- Featured Catalog Grid with Quick Size Selector -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="flex items-center justify-between mb-10">
            <div>
                <span class="text-xs font-semibold uppercase tracking-[0.04em] text-hoyos-red">Latest Drops</span>
                <h2 class="text-2xl font-display text-white tracking-wide mt-1">
                    Signature Apparel
                </h2>
            </div>
            <a href="{{ route('category.show') }}" class="text-xs font-semibold uppercase tracking-[0.04em] text-hoyos-muted hover:text-hoyos-red transition-colors flex items-center gap-1">
                View All Items →
            </a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 md:gap-6">
            @foreach($featuredProducts as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
    </section>
</x-hoyos-layout>
