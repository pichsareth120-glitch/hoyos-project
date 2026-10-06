<x-guest-layout>
    <div class="w-full max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-16 items-center">
        <!-- Left: Login Form -->
        <div class="max-w-md w-full mx-auto space-y-8">
            <div class="space-y-2">
                <h1 class="text-3xl sm:text-4xl font-display text-white tracking-wide">
                    Hi, Welcome Back!
                </h1>
                <p class="text-xs text-hoyos-muted">
                    Enter your credentials to access your dark wardrobe.
                </p>
            </div>

            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{ showPassword: false }">
                @csrf

                <!-- Email Input -->
                <div class="space-y-1.5">
                    <label for="email" class="block text-xs font-semibold text-hoyos-muted uppercase tracking-wider">
                        Email
                    </label>
                    <input 
                        id="email" 
                        type="email" 
                        name="email" 
                        value="{{ old('email') }}" 
                        placeholder="example@gmail.com"
                        required 
                        autofocus
                        class="w-full px-4 py-3 rounded-xl bg-[#F0D5D9] text-gray-900 placeholder-gray-500 font-medium text-sm focus:outline-none focus:ring-2 focus:ring-hoyos-red border-none transition-all"
                    />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>

                <!-- Password Input -->
                <div class="space-y-1.5">
                    <label for="password" class="block text-xs font-semibold text-hoyos-muted uppercase tracking-wider">
                        Password
                    </label>
                    <div class="relative">
                        <input 
                            id="password" 
                            :type="showPassword ? 'text' : 'password'"
                            name="password" 
                            placeholder="Enter your Password"
                            required 
                            class="w-full px-4 py-3 pr-11 rounded-xl bg-[#F0D5D9] text-gray-900 placeholder-gray-500 font-medium text-sm focus:outline-none focus:ring-2 focus:ring-hoyos-red border-none transition-all"
                        />
                        <button 
                            type="button" 
                            @click="showPassword = !showPassword"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-600 hover:text-black transition-colors"
                        >
                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between text-xs pt-1">
                    <label for="remember_me" class="flex items-center gap-2 cursor-pointer select-none">
                        <input 
                            id="remember_me" 
                            type="checkbox" 
                            name="remember" 
                            class="w-4 h-4 rounded bg-hoyos-card border-hoyos-border text-hoyos-red focus:ring-hoyos-red focus:ring-offset-hoyos-bg"
                        />
                        <span class="text-white font-medium">Remember Me</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-hoyos-red hover:text-hoyos-redHover font-semibold transition-colors">
                            Forgot Password?
                        </a>
                    @endif
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full py-3.5 rounded-full bg-hoyos-darkred/90 hover:bg-hoyos-red text-white text-sm font-bold uppercase tracking-wider transition-all duration-300 border border-hoyos-red/60 shadow-crimson-subtle hover:shadow-crimson-glow"
                >
                    Login
                </button>

                <!-- Divider -->
                <div class="relative flex items-center justify-center py-2">
                    <div class="border-t border-hoyos-border w-full"></div>
                    <span class="bg-hoyos-bg px-3 text-xs text-hoyos-dim uppercase tracking-wider font-semibold">Or With</span>
                    <div class="border-t border-hoyos-border w-full"></div>
                </div>

                <!-- Social Login -->
                <button 
                    type="button" 
                    class="w-full py-3 rounded-full bg-hoyos-card border border-hoyos-border hover:border-hoyos-borderBright text-white text-xs font-bold flex items-center justify-center gap-2.5 transition-all"
                >
                    <svg class="w-4 h-4 text-hoyos-red" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/>
                    </svg>
                    Login with Google
                </button>

                <!-- Signup Link -->
                <p class="text-center text-xs text-hoyos-muted pt-2">
                    Don't have an account? 
                    <a href="{{ route('register') }}" class="text-hoyos-red font-bold hover:underline ml-1">
                        Sign Up
                    </a>
                </p>
            </form>
        </div>

        <!-- Right: Chrome Logo Hero Showcase Card (Figma right panel) -->
        <div class="hidden lg:block">
            <div class="relative rounded-3xl overflow-hidden border-2 border-hoyos-red shadow-[0_0_35px_rgba(255,0,46,0.4)] aspect-[4/5] bg-black flex items-center justify-center p-8">
                <!-- Cosmic nebula background -->
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-hoyos-darkred/60 via-hoyos-bg to-black opacity-90"></div>
                <img 
                    src="{{ asset('images/hoyos/a9621747-d641-4a2f-8180-6c5743bfe268 1.png') }}" 
                    alt="HOYOS Chrome Insignia" 
                    class="relative z-10 w-full max-w-md object-contain drop-shadow-[0_0_25px_rgba(255,0,46,0.6)]"
                />
            </div>
        </div>
    </div>
</x-guest-layout>
