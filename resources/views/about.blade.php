<x-hoyos-layout title="About Us">
    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
        <!-- Ambient Background Glow -->
        <div class="absolute top-1/3 left-1/2 -translate-x-1/2 w-96 h-96 bg-hoyos-red/10 blur-[120px] pointer-events-none"></div>

        <div class="relative z-10 space-y-12 text-center sm:text-left">
            <!-- Headline -->
            <div class="space-y-4 text-center">
                <h1 class="text-4xl sm:text-6xl font-display text-white tracking-widest uppercase drop-shadow-[0_0_15px_rgba(255,0,46,0.5)]">
                    About Us
                </h1>
                <p class="text-base sm:text-lg text-hoyos-red italic font-semibold tracking-wide">
                    HOYOS — your outfit speaks louder than words.
                </p>
                <div class="w-20 h-1 bg-hoyos-red mx-auto rounded-full shadow-[0_0_8px_#FF002E]"></div>
            </div>

            <!-- Story Body Text from Figma -->
            <div class="space-y-8 text-sm sm:text-base text-hoyos-muted leading-relaxed font-light">
                <p>
                    <strong class="text-white font-bold">HOYOS</strong> is a streetwear brand born from the collision of past and future — where Y2K nostalgia meets modern rebellion. Rooted in gothic aesthetics and dark alternative culture, HOYOS creates pieces that go beyond fashion and into self-expression.
                </p>

                <p>
                    We design for a generation that refuses to blend in. Every collection is influenced by bold subcultures, digital-era energy, and the raw attitude of Gen Z. From sharp, aggressive silhouettes to subtle demon-inspired details, each design carries a sense of mystery, power, and individuality. Our goal is not just to follow trends, but to reshape them into something darker, stronger, and more personal.
                </p>

                <p>
                    At HOYOS, we believe clothing is more than just what you wear — it’s how you present your identity to the world. That’s why we focus on combining quality materials with distinctive design, ensuring each piece feels as good as it looks. The balance between vintage inspiration and futuristic styling defines our aesthetic, creating a unique visual language that stands out in today’s streetwear scene.
                </p>

                <p>
                    HOYOS represents confidence, edge, and freedom. It’s for those who embrace being different, who are drawn to bold visuals and unapologetic style. We are building more than a brand — we are building a culture where your outfit becomes your statement, and your presence speaks before you do.
                </p>
            </div>

            <!-- Call to Action -->
            <div class="text-center pt-8">
                <a 
                    href="{{ route('category.show') }}" 
                    class="inline-block px-10 py-4 rounded-full bg-hoyos-red hover:bg-hoyos-redHover text-white text-xs font-black uppercase tracking-widest transition-all shadow-crimson-glow hover:shadow-[0_0_30px_rgba(255,0,46,0.8)]"
                >
                    Explore The Drops
                </a>
            </div>
        </div>
    </div>
</x-hoyos-layout>
