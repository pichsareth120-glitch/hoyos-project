<x-hoyos-layout title="My Account - Hoyos">
    <div 
        x-data="{
            activeTab: '{{ $tab }}',
            showAddAddress: {{ $errors->hasAny(['recipient_name', 'recipient_phone', 'province', 'address_line', 'notes']) ? 'true' : 'false' }}
        }"
        class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12"
    >
        <!-- Header -->
        <div class="mb-8 pb-6 border-b border-hoyos-border flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-hoyos-card border border-hoyos-border flex items-center justify-center text-hoyos-red font-display text-xl shadow-glow">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-display text-white uppercase tracking-wider">
                        {{ $user->name }}
                    </h1>
                    <p class="text-xs text-hoyos-muted font-mono mt-0.5">
                        {{ $user->email }} • Member since {{ $user->created_at->format('M Y') }}
                    </p>
                </div>
            </div>

            <!-- Sign Out Button -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button 
                    type="submit" 
                    class="px-4 py-2 bg-hoyos-card hover:bg-hoyos-hover border border-hoyos-border rounded-xl text-xs font-bold text-hoyos-muted hover:text-white uppercase tracking-wider transition-colors flex items-center gap-2"
                >
                    <svg class="w-4 h-4 text-hoyos-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Sign Out
                </button>
            </form>
        </div>

        <!-- Feedback Messages -->
        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-xs text-emerald-400 flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 rounded-xl bg-hoyos-red/10 border border-hoyos-red/30 text-xs text-hoyos-red flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Sidebar Navigation Tabs -->
            <div class="lg:col-span-3 space-y-1 bg-hoyos-surface border border-hoyos-border rounded-2xl p-2.5">
                <button 
                    type="button" 
                    @click="activeTab = 'personal'"
                    :class="activeTab === 'personal' ? 'bg-hoyos-red text-white font-bold shadow-glow' : 'text-hoyos-muted hover:text-white hover:bg-hoyos-card'"
                    class="w-full text-left px-4 py-3 rounded-xl text-xs uppercase tracking-wider transition-all flex items-center gap-3"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Personal Details
                </button>

                <button 
                    type="button" 
                    @click="activeTab = 'addresses'"
                    :class="activeTab === 'addresses' ? 'bg-hoyos-red text-white font-bold shadow-glow' : 'text-hoyos-muted hover:text-white hover:bg-hoyos-card'"
                    class="w-full text-left px-4 py-3 rounded-xl text-xs uppercase tracking-wider transition-all flex items-center gap-3"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Saved Addresses ({{ $addresses->count() }})
                </button>

                <button 
                    type="button" 
                    @click="activeTab = 'orders'"
                    :class="activeTab === 'orders' ? 'bg-hoyos-red text-white font-bold shadow-glow' : 'text-hoyos-muted hover:text-white hover:bg-hoyos-card'"
                    class="w-full text-left px-4 py-3 rounded-xl text-xs uppercase tracking-wider transition-all flex items-center gap-3"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    My Orders ({{ $orders->count() }})
                </button>
            </div>

            <!-- Main Tab Content Area -->
            <div class="lg:col-span-9">
                <!-- TAB 1: Personal Details & Password -->
                <div x-show="activeTab === 'personal'" class="space-y-8" x-cloak>
                    <!-- Profile Form (Figma: User edit profile.png) -->
                    <div class="bg-hoyos-surface border border-hoyos-border rounded-2xl p-6 sm:p-8">
                        <h2 class="text-base font-semibold text-white uppercase tracking-[0.04em] mb-6 pb-3 border-b border-hoyos-border">
                            Personal Information
                        </h2>

                        <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-5">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-white uppercase tracking-wider mb-2">Title</label>
                                    <select 
                                        name="title" 
                                        class="w-full bg-hoyos-card border border-hoyos-border focus:border-hoyos-red rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none"
                                    >
                                        <option value="">Select</option>
                                        <option value="Mr." {{ old('title', $user->title) === 'Mr.' ? 'selected' : '' }}>Mr.</option>
                                        <option value="Ms." {{ old('title', $user->title) === 'Ms.' ? 'selected' : '' }}>Ms.</option>
                                        <option value="Mx." {{ old('title', $user->title) === 'Mx.' ? 'selected' : '' }}>Mx.</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-white uppercase tracking-wider mb-2">First Name</label>
                                    <input 
                                        type="text" 
                                        name="first_name" 
                                        value="{{ old('first_name', $user->first_name) }}"
                                        placeholder="First name"
                                        class="w-full bg-hoyos-card border border-hoyos-border focus:border-hoyos-red rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-white uppercase tracking-wider mb-2">Last Name</label>
                                    <input 
                                        type="text" 
                                        name="last_name" 
                                        value="{{ old('last_name', $user->last_name) }}"
                                        placeholder="Last name"
                                        class="w-full bg-hoyos-card border border-hoyos-border focus:border-hoyos-red rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-white uppercase tracking-wider mb-2">Display / Full Name *</label>
                                    <input 
                                        type="text" 
                                        name="name" 
                                        value="{{ old('name', $user->name) }}"
                                        required
                                        class="w-full bg-hoyos-card border border-hoyos-border focus:border-hoyos-red rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none"
                                    />
                                    @error('name')
                                        <p class="text-[11px] text-hoyos-red mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-white uppercase tracking-wider mb-2">Email Address *</label>
                                    <input 
                                        type="email" 
                                        name="email" 
                                        value="{{ old('email', $user->email) }}"
                                        required
                                        class="w-full bg-hoyos-card border border-hoyos-border focus:border-hoyos-red rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none"
                                    />
                                    @error('email')
                                        <p class="text-[11px] text-hoyos-red mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-white uppercase tracking-wider mb-2">Phone Number</label>
                                    <input 
                                        type="text" 
                                        name="phone" 
                                        value="{{ old('phone', $user->phone) }}"
                                        placeholder="012 345 678"
                                        class="w-full bg-hoyos-card border border-hoyos-border focus:border-hoyos-red rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:outline-none"
                                    />
                                    @error('phone')
                                        <p class="text-[11px] text-hoyos-red mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-white uppercase tracking-wider mb-2">Date of Birth</label>
                                    @php
                                        $dobParts = $user->date_of_birth ? explode('-', $user->date_of_birth->format('Y-m-d')) : [null, null, null];
                                    @endphp
                                    <div class="grid grid-cols-3 gap-2">
                                        <input 
                                            type="number" 
                                            name="birth_day" 
                                            placeholder="DD" 
                                            min="1" 
                                            max="31" 
                                            value="{{ old('birth_day', $dobParts[2] ?? '') }}"
                                            class="w-full bg-hoyos-card border border-hoyos-border focus:border-hoyos-red rounded-xl px-2 py-2.5 text-xs text-center text-white font-mono focus:outline-none"
                                        />
                                        <input 
                                            type="number" 
                                            name="birth_month" 
                                            placeholder="MM" 
                                            min="1" 
                                            max="12" 
                                            value="{{ old('birth_month', $dobParts[1] ?? '') }}"
                                            class="w-full bg-hoyos-card border border-hoyos-border focus:border-hoyos-red rounded-xl px-2 py-2.5 text-xs text-center text-white font-mono focus:outline-none"
                                        />
                                        <input 
                                            type="number" 
                                            name="birth_year" 
                                            placeholder="YYYY" 
                                            min="1920" 
                                            max="{{ date('Y') }}" 
                                            value="{{ old('birth_year', $dobParts[0] ?? '') }}"
                                            class="w-full bg-hoyos-card border border-hoyos-border focus:border-hoyos-red rounded-xl px-2 py-2.5 text-xs text-center text-white font-mono focus:outline-none"
                                        />
                                    </div>
                                    @error('birth_day')
                                        <p class="text-[11px] text-hoyos-red mt-1">{{ $message }}</p>
                                    @enderror
                                    @error('birth_month')
                                        <p class="text-[11px] text-hoyos-red mt-1">{{ $message }}</p>
                                    @enderror
                                    @error('birth_year')
                                        <p class="text-[11px] text-hoyos-red mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="pt-4 flex justify-end">
                                <button 
                                    type="submit" 
                                    class="px-6 py-2.5 bg-hoyos-red hover:bg-hoyos-red-hover text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-glow"
                                >
                                    Save Profile Changes
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Password Update Form -->
                    <div class="bg-hoyos-surface border border-hoyos-border rounded-2xl p-6 sm:p-8">
                        <h2 class="text-base font-semibold text-white uppercase tracking-[0.04em] mb-6 pb-3 border-b border-hoyos-border">
                            Security & Password
                        </h2>

                        <form method="POST" action="{{ route('account.password.update') }}" class="space-y-4 max-w-md">
                            @csrf
                            @method('PUT')

                            <div>
                                <label class="block text-xs font-bold text-white uppercase tracking-wider mb-2">Current Password</label>
                                <input 
                                    type="password" 
                                    name="current_password" 
                                    required
                                    class="w-full bg-hoyos-card border border-hoyos-border focus:border-hoyos-red rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none"
                                />
                                @error('current_password')
                                    <p class="text-[11px] text-hoyos-red mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-white uppercase tracking-wider mb-2">New Password</label>
                                <input 
                                    type="password" 
                                    name="password" 
                                    required
                                    class="w-full bg-hoyos-card border border-hoyos-border focus:border-hoyos-red rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none"
                                />
                                @error('password')
                                    <p class="text-[11px] text-hoyos-red mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-white uppercase tracking-wider mb-2">Confirm New Password</label>
                                <input 
                                    type="password" 
                                    name="password_confirmation" 
                                    required
                                    class="w-full bg-hoyos-card border border-hoyos-border focus:border-hoyos-red rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none"
                                />
                            </div>

                            <div class="pt-3">
                                <button 
                                    type="submit" 
                                    class="px-6 py-2.5 bg-white hover:bg-gray-100 text-black text-xs font-bold uppercase tracking-wider rounded-xl transition-colors"
                                >
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- TAB 2: Saved Addresses -->
                <div x-show="activeTab === 'addresses'" class="space-y-6" x-cloak>
                    <div class="flex items-center justify-between pb-4 border-b border-hoyos-border">
                        <div>
                            <h2 class="text-base font-semibold text-white uppercase tracking-[0.04em]">
                                Saved Shipping Addresses
                            </h2>
                            <p class="text-xs text-hoyos-muted mt-0.5">
                                Addresses automatically prefill during your 2-step checkout.
                            </p>
                        </div>
                        <button 
                            type="button" 
                            @click="showAddAddress = !showAddAddress"
                            class="px-4 py-2 bg-hoyos-red hover:bg-hoyos-red-hover text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-glow flex items-center gap-1.5"
                        >
                            <span x-text="showAddAddress ? 'Close Form' : '+ Add Address'"></span>
                        </button>
                    </div>

                    <!-- Add Address Drawer/Form -->
                    <div 
                        x-show="showAddAddress" 
                        x-transition 
                        class="bg-hoyos-card border border-hoyos-border rounded-2xl p-6"
                    >
                        <h3 class="text-xs font-bold text-white uppercase tracking-wider mb-4 pb-2 border-b border-hoyos-border">
                            Add New Address
                        </h3>

                        <form method="POST" action="{{ route('account.address.store') }}" class="space-y-4">
                            @csrf

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-white uppercase tracking-wider mb-1.5">Recipient Name *</label>
                                    <input 
                                        type="text" 
                                        name="recipient_name" 
                                        value="{{ old('recipient_name', $user->name) }}"
                                        required
                                        class="w-full bg-hoyos-surface border border-hoyos-border focus:border-hoyos-red rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none"
                                    />
                                    @error('recipient_name')
                                        <p class="text-[11px] text-hoyos-red mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-white uppercase tracking-wider mb-1.5">Phone Number *</label>
                                    <input 
                                        type="text" 
                                        name="recipient_phone" 
                                        value="{{ old('recipient_phone', $user->phone) }}"
                                        required
                                        placeholder="012 345 678"
                                        class="w-full bg-hoyos-surface border border-hoyos-border focus:border-hoyos-red rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:outline-none"
                                    />
                                    @error('recipient_phone')
                                        <p class="text-[11px] text-hoyos-red mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-white uppercase tracking-wider mb-1.5">Province / City *</label>
                                    <select 
                                        name="province" 
                                        required
                                        class="w-full bg-hoyos-surface border border-hoyos-border focus:border-hoyos-red rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none"
                                    >
                                        <option value="Phnom Penh">Phnom Penh</option>
                                        <option value="Kandal">Kandal</option>
                                        <option value="Siem Reap">Siem Reap</option>
                                        <option value="Battambang">Battambang</option>
                                        <option value="Sihanoukville">Sihanoukville</option>
                                        <option value="Kampot">Kampot</option>
                                        <option value="Other Province">Other Province</option>
                                    </select>
                                    @error('province')
                                        <p class="text-[11px] text-hoyos-red mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold text-white uppercase tracking-wider mb-1.5">Address Line *</label>
                                    <input 
                                        type="text" 
                                        name="address_line" 
                                        required
                                        placeholder="Street, House No., Sangkat, Khan"
                                        class="w-full bg-hoyos-surface border border-hoyos-border focus:border-hoyos-red rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none"
                                    />
                                    @error('address_line')
                                        <p class="text-[11px] text-hoyos-red mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-white uppercase tracking-wider mb-1.5">Delivery Notes</label>
                                <input 
                                    type="text" 
                                    name="notes" 
                                    placeholder="e.g. Call before delivery, drop at front gate"
                                    class="w-full bg-hoyos-surface border border-hoyos-border focus:border-hoyos-red rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none"
                                />
                                @error('notes')
                                    <p class="text-[11px] text-hoyos-red mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex items-center gap-2 pt-1">
                                <input 
                                    type="checkbox" 
                                    name="is_default" 
                                    id="is_default" 
                                    value="1" 
                                    class="rounded bg-hoyos-surface border-hoyos-border text-hoyos-red focus:ring-0"
                                />
                                <label for="is_default" class="text-xs text-hoyos-muted cursor-pointer">
                                    Set as primary delivery address
                                </label>
                            </div>

                            <div class="pt-3 flex justify-end gap-3">
                                <button 
                                    type="button" 
                                    @click="showAddAddress = false"
                                    class="px-4 py-2 bg-hoyos-surface hover:bg-hoyos-hover border border-hoyos-border rounded-xl text-xs font-bold text-hoyos-muted uppercase tracking-wider"
                                >
                                    Cancel
                                </button>
                                <button 
                                    type="submit" 
                                    class="px-6 py-2 bg-hoyos-red hover:bg-hoyos-red-hover text-white text-xs font-bold uppercase tracking-wider rounded-xl shadow-glow"
                                >
                                    Save Address
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Address List Grid -->
                    @if($addresses->isEmpty())
                        <div class="bg-hoyos-surface border border-hoyos-border rounded-2xl p-8 text-center">
                            <p class="text-xs text-hoyos-muted">No saved addresses yet. Add one above to speed up checkout.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($addresses as $address)
                                <div class="bg-hoyos-surface border {{ $address->is_default ? 'border-hoyos-red/60 shadow-glow' : 'border-hoyos-border' }} rounded-2xl p-5 relative">
                                    @if($address->is_default)
                                        <span class="absolute top-4 right-4 px-2 py-0.5 rounded-full text-[9px] font-mono font-bold uppercase tracking-wider bg-hoyos-red/20 text-hoyos-red border border-hoyos-red/40">
                                            Default
                                        </span>
                                    @endif

                                    <h4 class="text-xs font-bold text-white uppercase tracking-wider">{{ $address->recipient_name }}</h4>
                                    <p class="text-xs font-mono text-hoyos-muted mt-0.5">{{ $address->recipient_phone }}</p>
                                    <p class="text-xs text-white mt-2">{{ $address->province }}</p>
                                    <p class="text-xs text-hoyos-muted mt-0.5">{{ $address->address_line }}</p>
                                    @if($address->notes)
                                        <p class="text-[11px] text-hoyos-dim italic mt-1.5">Note: {{ $address->notes }}</p>
                                    @endif

                                    <div class="mt-4 pt-3 border-t border-hoyos-border/50 flex justify-end">
                                        <form method="POST" action="{{ route('account.address.destroy', $address) }}" onsubmit="return confirm('Delete this address?');">
                                            @csrf
                                            @method('DELETE')
                                            <button 
                                                type="submit" 
                                                class="text-[11px] text-hoyos-dim hover:text-hoyos-red uppercase tracking-wider transition-colors"
                                            >
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- TAB 3: My Orders -->
                <div x-show="activeTab === 'orders'" class="space-y-4" x-cloak>
                    <div class="flex items-center justify-between pb-4 border-b border-hoyos-border">
                        <div>
                            <h2 class="text-base font-semibold text-white uppercase tracking-[0.04em]">
                                Order History
                            </h2>
                            <p class="text-xs text-hoyos-muted mt-0.5">
                                Track ongoing shipments and review receipts.
                            </p>
                        </div>
                    </div>

                    @if($orders->isEmpty())
                        <div class="bg-hoyos-surface border border-hoyos-border rounded-2xl p-8 text-center">
                            <p class="text-xs text-hoyos-muted">You have no orders on record.</p>
                            <a href="{{ route('products.index') }}" class="mt-3 inline-block px-4 py-2 bg-hoyos-red text-white text-xs font-bold uppercase tracking-wider rounded-xl">Shop Now</a>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($orders as $order)
                                <div class="bg-hoyos-surface border border-hoyos-border rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                                        <div class="flex items-center gap-3">
                                            <span class="font-mono text-sm font-bold text-white">#{{ $order->order_number }}</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase {{ $order->payment_status === 'paid' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400' }}">
                                                {{ $order->payment_status }}
                                            </span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-hoyos-card text-hoyos-muted border border-hoyos-border">
                                                {{ $order->shipment ? $order->shipment->status_label : $order->order_status }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-hoyos-muted mt-1">
                                            {{ $order->created_at->format('M d, Y') }} • {{ $order->items->sum('quantity') }} items • <strong class="text-white font-mono">${{ number_format($order->total_amount, 2) }} USD</strong>
                                        </p>
                                        @if($order->shipment && $order->shipment->tracking_number)
                                            <p class="text-[11px] font-mono text-hoyos-red mt-1">
                                                J&T: {{ $order->shipment->tracking_number }}
                                            </p>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0">
                                        <a 
                                            href="{{ route('orders.show', ['orderNumber' => $order->order_number]) }}" 
                                            class="px-3 py-1.5 bg-hoyos-card hover:bg-hoyos-hover border border-hoyos-border rounded-lg text-xs font-bold text-white uppercase tracking-wider"
                                        >
                                            Receipt
                                        </a>
                                        <a 
                                            href="{{ route('orders.track', ['orderNumber' => $order->order_number]) }}" 
                                            class="px-3.5 py-1.5 bg-hoyos-red hover:bg-hoyos-red-hover rounded-lg text-xs font-bold text-white uppercase tracking-wider shadow-glow"
                                        >
                                            Track
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-hoyos-layout>
