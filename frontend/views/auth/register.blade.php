<x-guest-layout>
    <div class="w-full max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-16 items-center">
        <!-- Left: Registration Form -->
        <div class="max-w-md w-full mx-auto space-y-6">
            <div class="space-y-1.5">
                <h1 class="text-3xl sm:text-4xl font-display text-white tracking-wide">
                    Create an account
                </h1>
                <p class="text-xs text-hoyos-muted">
                    Connect with your friends today! Join the HOYOS movement.
                </p>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{ showPassword: false }">
                @csrf

                <!-- Name Input -->
                <div class="space-y-1">
                    <label for="name" class="block text-xs font-semibold text-hoyos-muted uppercase tracking-wider">
                        Full Name
                    </label>
                    <input 
                        id="name" 
                        type="text" 
                        name="name" 
                        value="{{ old('name') }}" 
                        placeholder="Enter your name"
                        required 
                        autofocus
                        class="w-full px-4 py-3 rounded-xl bg-[#14060a] border border-[rgba(255,0,46,0.25)] text-white placeholder-zinc-500 font-medium text-sm focus:outline-none focus:ring-2 focus:ring-hoyos-red focus:border-hoyos-red transition-all"
                    />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <!-- Email Input -->
                <div class="space-y-1">
                    <label for="email" class="block text-xs font-semibold text-hoyos-muted uppercase tracking-wider">
                        Email
                    </label>
                    <input 
                        id="email" 
                        type="email" 
                        name="email" 
                        value="{{ old('email') }}" 
                        placeholder="Enter your Email"
                        required 
                        class="w-full px-4 py-3 rounded-xl bg-[#14060a] border border-[rgba(255,0,46,0.25)] text-white placeholder-zinc-500 font-medium text-sm focus:outline-none focus:ring-2 focus:ring-hoyos-red focus:border-hoyos-red transition-all"
                    />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>

                <!-- Password Input -->
                <div class="space-y-1">
                    <label for="password" class="block text-xs font-semibold text-hoyos-muted uppercase tracking-wider">
                        Password
                    </label>
                    <div class="relative">
                        <input 
                            id="password" 
                            :type="showPassword ? 'text' : 'password'"
                            name="password" 
                            placeholder="Enter your password"
                            required 
                            class="w-full px-4 py-3 pr-11 rounded-xl bg-[#14060a] border border-[rgba(255,0,46,0.25)] text-white placeholder-zinc-500 font-medium text-sm focus:outline-none focus:ring-2 focus:ring-hoyos-red focus:border-hoyos-red transition-all"
                        />
                        <button 
                            type="button" 
                            @click="showPassword = !showPassword"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-white transition-colors"
                        >
                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>

                <!-- Password Confirmation Input -->
                <div class="space-y-1">
                    <label for="password_confirmation" class="block text-xs font-semibold text-hoyos-muted uppercase tracking-wider">
                        Confirm Password
                    </label>
                    <input 
                        id="password_confirmation" 
                        type="password" 
                        name="password_confirmation" 
                        placeholder="Confirm your password"
                        required 
                        class="w-full px-4 py-3 rounded-xl bg-[#14060a] border border-[rgba(255,0,46,0.25)] text-white placeholder-zinc-500 font-medium text-sm focus:outline-none focus:ring-2 focus:ring-hoyos-red focus:border-hoyos-red transition-all"
                    />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full py-3.5 rounded-full bg-hoyos-darkred/90 hover:bg-hoyos-red text-white text-sm font-bold uppercase tracking-wider transition-all duration-300 border border-hoyos-red/60 shadow-crimson-subtle hover:shadow-crimson-glow mt-2"
                >
                    Create Account
                </button>

                @if (app()->environment('local') && Route::has('auth.demo'))
                    <!-- Divider -->
                    <div class="relative flex items-center justify-center py-2">
                        <div class="border-t border-hoyos-border w-full"></div>
                        <span class="bg-hoyos-bg px-3 text-xs text-hoyos-muted uppercase tracking-wider font-semibold whitespace-nowrap">Or continue with</span>
                        <div class="border-t border-hoyos-border w-full"></div>
                    </div>

                    <!-- Demo Login -->
                    <a 
                        href="{{ route('auth.demo') }}" 
                        class="w-full py-3 rounded-full bg-hoyos-card border border-hoyos-border hover:border-hoyos-red text-white text-xs font-bold flex items-center justify-center gap-2.5 transition-all shadow-sm hover:shadow-crimson-subtle"
                    >
                        <svg class="w-4 h-4 text-hoyos-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Continue with demo account
                    </a>
                @endif

                <!-- Login Link -->
                <p class="text-center text-xs text-hoyos-muted pt-2">
                    Already have an account? 
                    <a href="{{ route('login') }}" class="text-hoyos-red font-bold hover:underline ml-1">
                        Sign In
                    </a>
                </p>
            </form>
        </div>

        <!-- Right: HOYOS Artwork Showcase Panel -->
        <div class="hidden lg:block w-full">
            <div class="relative rounded-3xl overflow-hidden border-2 border-hoyos-red shadow-[0_0_35px_rgba(255,0,46,0.4)] aspect-[4/5] w-full bg-[#080002] flex items-center justify-center p-6 sm:p-8">
                <!-- Soft background depth gradient -->
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-hoyos-darkred/40 via-[#080002] to-black opacity-80 pointer-events-none"></div>
                <img 
                    src="{{ asset('images/hoyos/hoyoslogo-feathered.png') }}" 
                    alt="HOYOS Artwork" 
                    class="relative z-10 w-full h-auto max-h-full object-contain scale-[1.20] drop-shadow-[0_0_25px_rgba(255,0,46,0.5)]"
                />
            </div>
        </div>
    </div>
</x-guest-layout>
