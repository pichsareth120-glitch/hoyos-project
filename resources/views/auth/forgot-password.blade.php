<x-guest-layout>
    <div class="max-w-md w-full mx-auto space-y-6">
        <div class="space-y-2">
            <h1 class="text-3xl font-display text-white tracking-wide">
                Reset Password
            </h1>
            <p class="text-xs text-hoyos-muted leading-relaxed">
                Forgot your password? No problem. Enter your email address and we will generate a password reset link.
            </p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

            <!-- Email Address -->
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
                    class="w-full px-4 py-3 rounded-xl bg-[#14060a] border border-[rgba(255,0,46,0.25)] text-white placeholder-zinc-500 font-medium text-sm focus:outline-none focus:ring-2 focus:ring-hoyos-red focus:border-hoyos-red transition-all"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>

            <button 
                type="submit"
                class="w-full py-3.5 rounded-full bg-hoyos-darkred/90 hover:bg-hoyos-red text-white text-sm font-bold uppercase tracking-wider transition-all duration-300 border border-hoyos-red/60 shadow-crimson-subtle hover:shadow-crimson-glow"
            >
                Email Password Reset Link
            </button>

            <div class="text-center pt-2">
                <a href="{{ route('login') }}" class="text-xs text-hoyos-muted hover:text-white transition-colors">
                    Back to Login
                </a>
            </div>
        </form>
    </div>
</x-guest-layout>
