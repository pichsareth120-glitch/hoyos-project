<x-hoyos-layout title="My Account - Hoyos">
    <div 
        x-data="{
            activeTab: '{{ $tab }}',
            showAddAddress: {{ $errors->hasAny(['recipient_name', 'recipient_phone', 'province', 'address_line', 'notes']) ? 'true' : 'false' }},
            orderFilter: 'all',
            passwordInput: '',
            confirmDeleteOpen: false,
            deleteAddressAction: '',
            get passwordStrength() {
                if (!this.passwordInput) return 0;
                let score = 0;
                if (this.passwordInput.length >= 8) score += 25;
                if (/[A-Z]/.test(this.passwordInput)) score += 25;
                if (/[0-9]/.test(this.passwordInput)) score += 25;
                if (/[^A-Za-z0-9]/.test(this.passwordInput)) score += 25;
                return score;
            },
            get passwordStrengthLabel() {
                if (!this.passwordInput) return '';
                if (this.passwordStrength <= 25) return 'Weak';
                if (this.passwordStrength <= 50) return 'Fair';
                if (this.passwordStrength <= 75) return 'Good';
                return 'Strong';
            },
            get passwordStrengthColor() {
                if (this.passwordStrength <= 25) return 'bg-red-500';
                if (this.passwordStrength <= 50) return 'bg-amber-400';
                if (this.passwordStrength <= 75) return 'bg-emerald-400';
                return 'bg-emerald-500';
            }
        }"
        class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10"
    >
        <!-- Header -->
        <div class="mb-8 pb-6 border-b border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl glass-elevated border border-white/15 flex items-center justify-center text-hoyos-red font-display text-2xl shadow-crimson-subtle relative">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                    <span class="absolute bottom-1 right-1 w-3 h-3 rounded-full bg-emerald-400 ring-2 ring-[#080103]"></span>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-display text-white uppercase tracking-wider">
                        {{ $user->name }}
                    </h1>
                    <p class="text-xs text-white/50 font-mono mt-0.5">
                        {{ $user->email }} • Member since {{ $user->created_at->format('M Y') }}
                    </p>
                </div>
            </div>

            <!-- Sign Out Button -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-ui.button 
                    type="submit" 
                    variant="secondary"
                    size="sm"
                >
                    <svg class="w-4 h-4 text-hoyos-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Sign Out</span>
                </x-ui.button>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Sticky Sidebar Navigation Tabs (Horizontal scroll on mobile) -->
            <aside class="lg:col-span-3 lg:sticky lg:top-24 z-20">
                <div class="glass p-2 sm:p-2.5 rounded-2xl flex lg:flex-col overflow-x-auto gap-1.5 no-scrollbar">
                    <button 
                        type="button" 
                        @click="activeTab = 'personal'"
                        :class="activeTab === 'personal' ? 'bg-hoyos-red text-white shadow-crimson-subtle border-hoyos-red' : 'text-white/60 hover:text-white hover:bg-white/[0.05] border-transparent'"
                        class="flex-1 lg:w-full text-left px-4 py-3 rounded-xl text-xs font-mono font-bold uppercase tracking-wider transition-all duration-200 border flex items-center justify-between gap-3 shrink-0"
                    >
                        <span class="flex items-center gap-2.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span>Personal Details</span>
                        </span>
                    </button>

                    <button 
                        type="button" 
                        @click="activeTab = 'addresses'"
                        :class="activeTab === 'addresses' ? 'bg-hoyos-red text-white shadow-crimson-subtle border-hoyos-red' : 'text-white/60 hover:text-white hover:bg-white/[0.05] border-transparent'"
                        class="flex-1 lg:w-full text-left px-4 py-3 rounded-xl text-xs font-mono font-bold uppercase tracking-wider transition-all duration-200 border flex items-center justify-between gap-3 shrink-0"
                    >
                        <span class="flex items-center gap-2.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Saved Addresses</span>
                        </span>
                        <span 
                            class="px-2 py-0.5 rounded-full text-[10px] font-mono transition-colors"
                            :class="activeTab === 'addresses' ? 'bg-black/30 text-white' : 'bg-white/[0.08] text-white/60'"
                        >
                            {{ $addresses->count() }}
                        </span>
                    </button>

                    <button 
                        type="button" 
                        @click="activeTab = 'orders'"
                        :class="activeTab === 'orders' ? 'bg-hoyos-red text-white shadow-crimson-subtle border-hoyos-red' : 'text-white/60 hover:text-white hover:bg-white/[0.05] border-transparent'"
                        class="flex-1 lg:w-full text-left px-4 py-3 rounded-xl text-xs font-mono font-bold uppercase tracking-wider transition-all duration-200 border flex items-center justify-between gap-3 shrink-0"
                    >
                        <span class="flex items-center gap-2.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <span>My Orders</span>
                        </span>
                        <span 
                            class="px-2 py-0.5 rounded-full text-[10px] font-mono transition-colors"
                            :class="activeTab === 'orders' ? 'bg-black/30 text-white' : 'bg-white/[0.08] text-white/60'"
                        >
                            {{ $orders->count() }}
                        </span>
                    </button>
                </div>
            </aside>

            <!-- Main Tab Content Area -->
            <main class="lg:col-span-9">
                <!-- TAB 1: Personal Details & Password -->
                <div x-show="activeTab === 'personal'" class="space-y-8" x-cloak>
                    <!-- Personal Information Card -->
                    <div class="glass rounded-2xl p-6 sm:p-8">
                        <div class="pb-4 mb-6 border-b border-white/10 flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-mono font-bold text-white uppercase tracking-wider">
                                    Personal Information
                                </h2>
                                <p class="text-xs text-white/50 mt-0.5">
                                    Update your name, contact phone, and account details.
                                </p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-5">
                            @csrf
                            @method('PUT')

                            <!-- 3-Column Name Row -->
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                                <div class="sm:col-span-3 space-y-1.5">
                                    <label class="block text-xs font-mono font-bold text-white uppercase tracking-wider">Title</label>
                                    <select 
                                        name="title" 
                                        class="w-full glass-input px-3.5 py-2.5 text-xs text-white focus:outline-none"
                                    >
                                        <option value="" class="bg-[#120407]">Select</option>
                                        <option value="Mr." class="bg-[#120407]" {{ old('title', $user->title) === 'Mr.' ? 'selected' : '' }}>Mr.</option>
                                        <option value="Ms." class="bg-[#120407]" {{ old('title', $user->title) === 'Ms.' ? 'selected' : '' }}>Ms.</option>
                                        <option value="Mx." class="bg-[#120407]" {{ old('title', $user->title) === 'Mx.' ? 'selected' : '' }}>Mx.</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-4 space-y-1.5">
                                    <label class="block text-xs font-mono font-bold text-white uppercase tracking-wider">First Name</label>
                                    <input 
                                        type="text" 
                                        name="first_name" 
                                        value="{{ old('first_name', $user->first_name) }}"
                                        placeholder="First name"
                                        class="w-full glass-input px-4 py-2.5 text-xs text-white focus:outline-none"
                                    />
                                </div>
                                <div class="sm:col-span-5 space-y-1.5">
                                    <label class="block text-xs font-mono font-bold text-white uppercase tracking-wider">Last Name</label>
                                    <input 
                                        type="text" 
                                        name="last_name" 
                                        value="{{ old('last_name', $user->last_name) }}"
                                        placeholder="Last name"
                                        class="w-full glass-input px-4 py-2.5 text-xs text-white focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-mono font-bold text-white uppercase tracking-wider">Display / Full Name *</label>
                                    <input 
                                        type="text" 
                                        name="name" 
                                        value="{{ old('name', $user->name) }}"
                                        required
                                        class="w-full glass-input px-4 py-2.5 text-xs text-white focus:outline-none"
                                    />
                                    @error('name')
                                        <p class="text-[11px] text-hoyos-red font-medium mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-mono font-bold text-white uppercase tracking-wider">Email Address *</label>
                                    <input 
                                        type="email" 
                                        name="email" 
                                        value="{{ old('email', $user->email) }}"
                                        required
                                        class="w-full glass-input px-4 py-2.5 text-xs text-white focus:outline-none"
                                    />
                                    @error('email')
                                        <p class="text-[11px] text-hoyos-red font-medium mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-mono font-bold text-white uppercase tracking-wider">Phone Number</label>
                                    <input 
                                        type="text" 
                                        name="phone" 
                                        value="{{ old('phone', $user->phone) }}"
                                        placeholder="012 345 678"
                                        class="w-full glass-input px-4 py-2.5 text-xs text-white font-mono focus:outline-none"
                                    />
                                    @error('phone')
                                        <p class="text-[11px] text-hoyos-red font-medium mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Date of Birth (Day / Month / Year with explicit labels) -->
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-mono font-bold text-white uppercase tracking-wider">Date of Birth</label>
                                    @php
                                        $dobParts = $user->date_of_birth ? explode('-', $user->date_of_birth->format('Y-m-d')) : [null, null, null];
                                    @endphp
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <input 
                                                type="number" 
                                                name="birth_day" 
                                                placeholder="DD" 
                                                min="1" 
                                                max="31" 
                                                value="{{ old('birth_day', $dobParts[2] ?? '') }}"
                                                class="w-full glass-input px-2 py-2.5 text-xs text-center text-white font-mono focus:outline-none"
                                                aria-label="Day"
                                            />
                                        </div>
                                        <div>
                                            <input 
                                                type="number" 
                                                name="birth_month" 
                                                placeholder="MM" 
                                                min="1" 
                                                max="12" 
                                                value="{{ old('birth_month', $dobParts[1] ?? '') }}"
                                                class="w-full glass-input px-2 py-2.5 text-xs text-center text-white font-mono focus:outline-none"
                                                aria-label="Month"
                                            />
                                        </div>
                                        <div>
                                            <input 
                                                type="number" 
                                                name="birth_year" 
                                                placeholder="YYYY" 
                                                min="1920" 
                                                max="{{ date('Y') }}" 
                                                value="{{ old('birth_year', $dobParts[0] ?? '') }}"
                                                class="w-full glass-input px-2 py-2.5 text-xs text-center text-white font-mono focus:outline-none"
                                                aria-label="Year"
                                            />
                                        </div>
                                    </div>
                                    @error('birth_day')
                                        <p class="text-[11px] text-hoyos-red font-medium mt-1">{{ $message }}</p>
                                    @enderror
                                    @error('birth_month')
                                        <p class="text-[11px] text-hoyos-red font-medium mt-1">{{ $message }}</p>
                                    @enderror
                                    @error('birth_year')
                                        <p class="text-[11px] text-hoyos-red font-medium mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="pt-4 flex justify-end">
                                <x-ui.button 
                                    type="submit" 
                                    variant="default"
                                    size="md"
                                >
                                    Save Profile Changes
                                </x-ui.button>
                            </div>
                        </form>
                    </div>

                    <!-- Security & Password Card -->
                    <div class="glass rounded-2xl p-6 sm:p-8">
                        <div class="pb-4 mb-6 border-b border-white/10">
                            <h2 class="text-base font-mono font-bold text-white uppercase tracking-wider">
                                Security & Password
                            </h2>
                            <p class="text-xs text-white/50 mt-0.5">
                                Ensure your account uses a strong, unique password.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('account.password.update') }}" class="space-y-5 max-w-lg">
                            @csrf
                            @method('PUT')

                            <x-ui.input 
                                type="password" 
                                name="current_password" 
                                label="Current Password *"
                                required
                                :error="$errors->first('current_password')"
                            />

                            <div class="space-y-2">
                                <x-ui.input 
                                    type="password" 
                                    name="password" 
                                    label="New Password *"
                                    required
                                    x-model="passwordInput"
                                    :error="$errors->first('password')"
                                />

                                <!-- Password Strength Meter -->
                                <div x-show="passwordInput.length > 0" x-cloak class="space-y-1.5 pt-1">
                                    <div class="flex items-center justify-between text-[11px] font-mono">
                                        <span class="text-white/50">Password Strength:</span>
                                        <span class="font-bold uppercase tracking-wider" :class="passwordStrengthLabel === 'Weak' ? 'text-red-400' : (passwordStrengthLabel === 'Fair' ? 'text-amber-400' : 'text-emerald-400')" x-text="passwordStrengthLabel"></span>
                                    </div>
                                    <div class="w-full h-1.5 rounded-full bg-white/10 overflow-hidden">
                                        <div 
                                            class="h-full transition-all duration-300"
                                            :class="passwordStrengthColor"
                                            :style="'width: ' + passwordStrength + '%'"
                                        ></div>
                                    </div>
                                    <p class="text-[10px] text-white/40">Use 8+ characters with uppercase letters, numbers & symbols.</p>
                                </div>
                            </div>

                            <x-ui.input 
                                type="password" 
                                name="password_confirmation" 
                                label="Confirm New Password *"
                                required
                            />

                            <div class="pt-3 flex justify-start">
                                <x-ui.button 
                                    type="submit" 
                                    variant="default"
                                    size="md"
                                >
                                    Update Password
                                </x-ui.button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- TAB 2: Saved Addresses -->
                <div x-show="activeTab === 'addresses'" class="space-y-6" x-cloak>
                    <div class="flex items-center justify-between pb-4 border-b border-white/10">
                        <div>
                            <h2 class="text-base font-mono font-bold text-white uppercase tracking-wider">
                                Saved Shipping Addresses
                            </h2>
                            <p class="text-xs text-white/50 mt-0.5">
                                Addresses automatically prefill during your 2-step checkout.
                            </p>
                        </div>
                        <x-ui.button 
                            type="button" 
                            @click="showAddAddress = !showAddAddress"
                            variant="default"
                            size="sm"
                        >
                            <span x-text="showAddAddress ? 'Close Form' : '+ Add Address'"></span>
                        </x-ui.button>
                    </div>

                    <!-- Add Address Drawer/Form -->
                    <div 
                        x-show="showAddAddress" 
                        x-transition 
                        class="glass-elevated border border-white/15 rounded-2xl p-6"
                    >
                        <h3 class="text-xs font-mono font-bold text-white uppercase tracking-wider mb-4 pb-2 border-b border-white/10">
                            Add New Shipping Address
                        </h3>

                        <form method="POST" action="{{ route('account.address.store') }}" class="space-y-4">
                            @csrf

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <x-ui.input 
                                    name="recipient_name" 
                                    label="Recipient Name *"
                                    value="{{ old('recipient_name', $user->name) }}"
                                    required
                                    :error="$errors->first('recipient_name')"
                                />
                                <x-ui.input 
                                    name="recipient_phone" 
                                    label="Phone Number *"
                                    value="{{ old('recipient_phone', $user->phone) }}"
                                    placeholder="012 345 678"
                                    required
                                    :error="$errors->first('recipient_phone')"
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-mono font-bold text-white uppercase tracking-wider">Province / City *</label>
                                    <select 
                                        name="province" 
                                        required
                                        class="w-full glass-input px-3.5 py-2.5 text-xs text-white focus:outline-none"
                                    >
                                        <option value="Phnom Penh" class="bg-[#120407]">Phnom Penh</option>
                                        <option value="Kandal" class="bg-[#120407]">Kandal</option>
                                        <option value="Siem Reap" class="bg-[#120407]">Siem Reap</option>
                                        <option value="Battambang" class="bg-[#120407]">Battambang</option>
                                        <option value="Sihanoukville" class="bg-[#120407]">Sihanoukville</option>
                                        <option value="Kampot" class="bg-[#120407]">Kampot</option>
                                        <option value="Other Province" class="bg-[#120407]">Other Province</option>
                                    </select>
                                    @error('province')
                                        <p class="text-[11px] text-hoyos-red font-medium mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <x-ui.input 
                                        name="address_line" 
                                        label="Address Line *"
                                        placeholder="Street, House No., Sangkat, Khan"
                                        required
                                        :error="$errors->first('address_line')"
                                    />
                                </div>
                            </div>

                            <x-ui.input 
                                name="notes" 
                                label="Delivery Notes (Optional)"
                                placeholder="e.g. Call before delivery, drop at front gate"
                                :error="$errors->first('notes')"
                            />

                            <div class="flex items-center gap-2 pt-1">
                                <input 
                                    type="checkbox" 
                                    name="is_default" 
                                    id="is_default" 
                                    value="1" 
                                    class="rounded bg-white/10 border-white/20 text-hoyos-red focus:ring-hoyos-red"
                                />
                                <label for="is_default" class="text-xs text-white/70 cursor-pointer">
                                    Set as primary delivery address
                                </label>
                            </div>

                            <div class="pt-3 flex justify-end gap-3">
                                <x-ui.button 
                                    type="button" 
                                    @click="showAddAddress = false"
                                    variant="secondary"
                                    size="sm"
                                >
                                    Cancel
                                </x-ui.button>
                                <x-ui.button 
                                    type="submit" 
                                    variant="default"
                                    size="sm"
                                >
                                    Save Address
                                </x-ui.button>
                            </div>
                        </form>
                    </div>

                    <!-- Address List Grid -->
                    @if($addresses->isEmpty())
                        <div class="glass rounded-2xl p-8 text-center space-y-3">
                            <div class="w-12 h-12 rounded-xl glass mx-auto flex items-center justify-center text-white/40">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                            </div>
                            <p class="text-xs text-white/50">No saved addresses yet. Add one above to speed up checkout.</p>
                        </div>
                    @else
                        @php
                            $seenFingerprints = [];
                        @endphp
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($addresses as $address)
                                @php
                                    $fp = md5($address->recipient_name . '|' . $address->recipient_phone . '|' . $address->province . '|' . $address->address_line);
                                    $isDuplicate = in_array($fp, $seenFingerprints);
                                    $seenFingerprints[] = $fp;
                                @endphp
                                <div class="rounded-2xl p-5 relative transition-all duration-200 glass-interactive {{ $address->is_default ? 'border border-hoyos-red/60 shadow-[0_0_24px_rgba(255,0,46,0.22)] bg-hoyos-red/[0.04]' : 'border border-white/10' }}">
                                    <!-- Badges -->
                                    <div class="flex items-center gap-2 absolute top-4 right-4">
                                        @if($address->is_default)
                                            <span class="px-2.5 py-0.5 rounded-full text-[9px] font-mono font-bold uppercase tracking-wider bg-hoyos-red text-white shadow-crimson-subtle">
                                                DEFAULT
                                            </span>
                                        @endif
                                        @if($isDuplicate)
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-mono font-bold uppercase tracking-wider bg-amber-400/15 text-amber-300 border border-amber-400/30" title="This address appears more than once in the demo database.">
                                                DEMO DUPLICATE
                                            </span>
                                        @endif
                                    </div>

                                    <h3 class="text-xs font-mono font-bold text-white uppercase tracking-wider">{{ $address->recipient_name }}</h3>
                                    <p class="text-xs font-mono text-white/50 mt-0.5">{{ $address->recipient_phone }}</p>
                                    <p class="text-xs font-medium text-white mt-3">{{ $address->province }}</p>
                                    <p class="text-xs text-white/70 mt-0.5">{{ $address->address_line }}</p>
                                    @if($address->notes)
                                        <p class="text-[11px] text-white/40 italic mt-2">Note: {{ $address->notes }}</p>
                                    @endif

                                    <div class="mt-4 pt-3 border-t border-white/[0.08] flex items-center justify-between">
                                        <div>
                                            @if(!$address->is_default)
                                                <form method="POST" action="{{ route('account.address.store') }}">
                                                    @csrf
                                                    <input type="hidden" name="recipient_name" value="{{ $address->recipient_name }}">
                                                    <input type="hidden" name="recipient_phone" value="{{ $address->recipient_phone }}">
                                                    <input type="hidden" name="province" value="{{ $address->province }}">
                                                    <input type="hidden" name="address_line" value="{{ $address->address_line }}">
                                                    <input type="hidden" name="is_default" value="1">
                                                    <button type="submit" class="text-[11px] font-mono text-white/50 hover:text-white transition-colors">
                                                        Set as default
                                                    </button>
                                                </form>
                                            @endif
                                        </div>

                                        <button 
                                            type="button" 
                                            @click="deleteAddressAction = '{{ route('account.address.destroy', $address) }}'; confirmDeleteOpen = true"
                                            class="text-[11px] font-mono text-white/40 hover:text-hoyos-red uppercase tracking-wider transition-colors flex items-center gap-1"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- TAB 3: My Orders -->
                <div x-show="activeTab === 'orders'" class="space-y-6" x-cloak>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-white/10">
                        <div>
                            <h2 class="text-base font-mono font-bold text-white uppercase tracking-wider">
                                Order History
                            </h2>
                            <p class="text-xs text-white/50 mt-0.5">
                                Track ongoing shipments and review order receipts.
                            </p>
                        </div>

                        <!-- Status Filter Chips -->
                        <div class="flex items-center gap-1.5 p-1 rounded-xl glass border border-white/10 self-start sm:self-auto">
                            <button 
                                type="button" 
                                @click="orderFilter = 'all'"
                                :class="orderFilter === 'all' ? 'bg-hoyos-red text-white shadow-crimson-subtle' : 'text-white/60 hover:text-white'"
                                class="px-3 py-1 rounded-lg text-[11px] font-mono font-bold uppercase tracking-wider transition-colors"
                            >
                                All ({{ $orders->count() }})
                            </button>
                            <button 
                                type="button" 
                                @click="orderFilter = 'paid'"
                                :class="orderFilter === 'paid' ? 'bg-hoyos-red text-white shadow-crimson-subtle' : 'text-white/60 hover:text-white'"
                                class="px-3 py-1 rounded-lg text-[11px] font-mono font-bold uppercase tracking-wider transition-colors"
                            >
                                Paid
                            </button>
                            <button 
                                type="button" 
                                @click="orderFilter = 'pending'"
                                :class="orderFilter === 'pending' ? 'bg-hoyos-red text-white shadow-crimson-subtle' : 'text-white/60 hover:text-white'"
                                class="px-3 py-1 rounded-lg text-[11px] font-mono font-bold uppercase tracking-wider transition-colors"
                            >
                                Pending
                            </button>
                            <button 
                                type="button" 
                                @click="orderFilter = 'delivered'"
                                :class="orderFilter === 'delivered' ? 'bg-hoyos-red text-white shadow-crimson-subtle' : 'text-white/60 hover:text-white'"
                                class="px-3 py-1 rounded-lg text-[11px] font-mono font-bold uppercase tracking-wider transition-colors"
                            >
                                Delivered
                            </button>
                        </div>
                    </div>

                    @if($orders->isEmpty())
                        <div class="glass rounded-2xl p-10 text-center space-y-4">
                            <div class="w-16 h-16 rounded-2xl glass mx-auto flex items-center justify-center text-white/30">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <p class="text-xs text-white/50 font-mono uppercase tracking-wider">You have no orders on record yet.</p>
                            <a href="{{ route('category.show') }}" class="inline-block px-5 py-2.5 bg-hoyos-red hover:bg-hoyos-redHover text-white text-xs font-mono font-bold uppercase tracking-wider rounded-xl shadow-crimson-glow transition-all">
                                Explore Store Drops
                            </a>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($orders as $order)
                                @php
                                    $firstItem = $order->items->first();
                                    $itemImage = $firstItem?->variant?->product?->primaryImage ? asset($firstItem->variant->product->primaryImage->image_path) : asset('images/hoyos/0ffd9c02726576625184fe52b38ace6d.jpg');
                                    $shipmentStatus = $order->shipment ? $order->shipment->status : 'processing';
                                @endphp
                                <div 
                                    x-show="orderFilter === 'all' || (orderFilter === 'paid' && '{{ $order->payment_status }}' === 'paid') || (orderFilter === 'pending' && '{{ $order->payment_status }}' === 'pending') || (orderFilter === 'delivered' && '{{ $shipmentStatus }}' === 'delivered')"
                                    class="glass rounded-2xl p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-5 transition-all duration-200 glass-interactive"
                                >
                                    <div class="flex items-start sm:items-center gap-4 flex-1">
                                        <!-- Product Thumbnail Preview -->
                                        <img 
                                            src="{{ $itemImage }}" 
                                            alt="Order item"
                                            class="w-16 h-20 sm:w-20 sm:h-24 object-cover rounded-xl bg-black border border-white/10 shrink-0"
                                        />

                                        <div class="space-y-1.5 flex-1 min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="font-mono text-sm font-bold text-white tracking-wider">
                                                    #{{ $order->order_number }}
                                                </span>
                                                <x-ui.badge :variant="$order->payment_status" size="xs">
                                                    {{ $order->payment_status }}
                                                </x-ui.badge>
                                                <x-ui.badge variant="neutral" size="xs">
                                                    {{ $order->shipment ? $order->shipment->status_label : $order->order_status }}
                                                </x-ui.badge>
                                            </div>

                                            <p class="text-xs text-white/60">
                                                {{ $firstItem ? $firstItem->product_title : 'Streetwear apparel' }}
                                                @if($order->items->count() > 1)
                                                    <span class="text-white/40">+ {{ $order->items->count() - 1 }} more</span>
                                                @endif
                                            </p>

                                            <p class="text-[11px] text-white/50 font-mono">
                                                {{ $order->created_at->format('M d, Y') }} • {{ $order->items->sum('quantity') }} items • <strong class="text-white font-mono font-bold">${{ number_format($order->total_amount, 2) }} USD</strong>
                                            </p>

                                            @if($order->shipment && $order->shipment->tracking_number)
                                                <p class="text-[11px] font-mono text-hoyos-red flex items-center gap-1">
                                                    <span>J&T Express:</span>
                                                    <span class="font-bold underline">{{ $order->shipment->tracking_number }}</span>
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2.5 shrink-0 pt-3 md:pt-0 border-t md:border-t-0 border-white/[0.08]">
                                        <x-ui.button 
                                            href="{{ route('orders.show', ['orderNumber' => $order->order_number]) }}" 
                                            variant="secondary"
                                            size="sm"
                                        >
                                            Receipt
                                        </x-ui.button>
                                        <x-ui.button 
                                            href="{{ route('orders.track', ['orderNumber' => $order->order_number]) }}" 
                                            variant="default"
                                            size="sm"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Track
                                        </x-ui.button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>

    <!-- Confirm Address Delete Modal -->
    <x-ui.confirm-modal
        name="confirmDeleteOpen"
        title="Delete Address"
        description="Are you sure you want to remove this shipping address? This cannot be undone."
        actionDynamic="deleteAddressAction"
        method="DELETE"
        confirmText="Delete Address"
        cancelText="Keep Address"
        variant="destructive"
    />
</x-hoyos-layout>
