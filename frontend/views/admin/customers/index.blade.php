@extends('layouts.admin')

@section('title', 'Customer Accounts')

@section('content')
<div class="space-y-6">
    <!-- 1. PageHeader Section -->
    <x-ui.page-header
        eyebrow="User Management"
        title="Customer Accounts"
        subtitle="Manage client profiles, spend analytics, order history, and account active/suspended statuses."
    >
        <x-slot:status>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-mono bg-zinc-900/90 border border-white/[0.08] text-zinc-300">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Active Customers: <strong class="text-white font-mono">{{ $customerStats->active_count ?? 0 }}</strong></span>
            </span>
        </x-slot:status>
    </x-ui.page-header>

    @php
        $totalCustomersCount = (int) ($customerStats->total ?? 0);
        $activeCustomersCount = (int) ($customerStats->active_count ?? 0);
        $suspendedCustomersCount = (int) ($customerStats->suspended_count ?? 0);
        $newThisMonthCount = (int) ($customerStats->new_this_month ?? 0);

        $hasActiveFilters = request()->hasAny(['search', 'status', 'sort']);
    @endphp

    <!-- 2. KPI StatCards Row (2 cols on mobile <640px, 4 cols on desktop) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- 1. Total Customers -->
        <a href="{{ route('admin.customers.index') }}" class="group block h-full">
            <x-ui.stat-card
                title="Total Customers"
                :value="$totalCustomersCount"
                description="Registered client accounts"
                class="h-full group-hover:border-white/[0.2] transition-all cursor-pointer"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>
        </a>

        <!-- 2. Active Accounts -->
        <a href="{{ route('admin.customers.index', ['status' => 'active']) }}" class="group block h-full">
            <x-ui.stat-card
                title="Active Accounts"
                :value="$activeCustomersCount"
                description="Active login & checkout enabled"
                class="h-full group-hover:border-emerald-500/30 transition-all cursor-pointer"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>
        </a>

        <!-- 3. Suspended Accounts -->
        <a href="{{ route('admin.customers.index', ['status' => 'suspended']) }}" class="group block h-full">
            <x-ui.stat-card
                title="Suspended"
                :value="$suspendedCustomersCount"
                description="Access revoked / restricted"
                class="h-full group-hover:border-rose-500/30 transition-all cursor-pointer"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>
        </a>

        <!-- 4. New This Month -->
        <div class="block h-full">
            <x-ui.stat-card
                title="New This Month"
                :value="$newThisMonthCount"
                description="Joined {{ now()->format('F Y') }}"
                class="h-full"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-hoyos-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>
        </div>
    </div>

    <!-- 3. FilterBar Section (Single Search Input via component prop) -->
    <x-ui.filter-bar
        :action="route('admin.customers.index')"
        method="GET"
        search-placeholder="Search name, email, or phone..."
        :reset-url="route('admin.customers.index')"
        :has-active-filters="$hasActiveFilters"
    >
        <!-- Account Status Select -->
        <div class="w-full sm:w-44 flex-shrink-0">
            <select 
                name="status" 
                class="w-full bg-zinc-950/80 border border-white/[0.1] text-zinc-300 text-xs rounded-xl px-3 py-2 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all font-mono"
            >
                <option value="" class="bg-zinc-900 text-zinc-300">All Statuses</option>
                <option value="active" class="bg-zinc-900 text-zinc-300" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="suspended" class="bg-zinc-900 text-zinc-300" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
            </select>
        </div>

        <!-- Sort By Select -->
        <div class="w-full sm:w-48 flex-shrink-0">
            <select 
                name="sort" 
                class="w-full bg-zinc-950/80 border border-white/[0.1] text-zinc-300 text-xs rounded-xl px-3 py-2 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all font-mono"
            >
                <option value="newest" class="bg-zinc-900 text-zinc-300" {{ (empty(request('sort')) || request('sort') === 'newest') ? 'selected' : '' }}>Newest Registered</option>
                <option value="lifetime_spend" class="bg-zinc-900 text-zinc-300" {{ request('sort') === 'lifetime_spend' ? 'selected' : '' }}>Highest Spend</option>
                <option value="orders_count" class="bg-zinc-900 text-zinc-300" {{ request('sort') === 'orders_count' ? 'selected' : '' }}>Most Orders</option>
            </select>
        </div>
    </x-ui.filter-bar>

    <!-- 4. DataTable Card with correctly aligned thead -->
    <x-ui.data-table>
        <x-slot:thead>
            <tr>
                <th class="px-5 py-3.5 font-mono">CUSTOMER</th>
                <th class="px-5 py-3.5 font-mono">CONTACT</th>
                <th class="px-5 py-3.5 text-center font-mono">STATUS</th>
                <th class="px-5 py-3.5 text-center font-mono">ORDERS</th>
                <th class="px-5 py-3.5 text-right font-mono">LIFETIME SPEND</th>
                <th class="px-5 py-3.5 font-mono">REGISTERED</th>
                <th class="px-5 py-3.5 text-right font-mono">ACTIONS</th>
            </tr>
        </x-slot:thead>

        @forelse($customers as $customer)
            @php
                $words = explode(' ', trim($customer->name));
                $initials = count($words) >= 2 
                    ? strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1)) 
                    : strtoupper(substr($customer->name, 0, 2));
            @endphp
            <tr class="hover:bg-white/[0.02] transition-colors">
                <!-- 1. CUSTOMER (Initials + Name in sans-serif, ID in mono) -->
                <td class="px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-zinc-800 text-zinc-300 font-mono text-xs flex items-center justify-center border border-zinc-700 shrink-0 font-bold">
                            {{ $initials }}
                        </div>
                        <div class="min-w-0">
                            <a 
                                href="{{ route('admin.customers.show', $customer) }}"
                                class="font-medium text-white text-xs hover:text-hoyos-red transition-colors block truncate font-sans"
                            >
                                {{ $customer->name }}
                            </a>
                            <div class="text-[10px] font-mono text-admin-muted block mt-0.5">
                                ID #{{ $customer->id }}
                            </div>
                        </div>
                    </div>
                </td>

                <!-- 2. CONTACT (Email & Phone in mono) -->
                <td class="px-5 py-4 font-mono">
                    <div class="text-white text-xs font-mono">{{ $customer->email }}</div>
                    <div class="text-admin-muted font-mono text-[11px] mt-0.5">{{ $customer->phone ?: 'No phone' }}</div>
                </td>

                <!-- 3. STATUS (StatusBadge) -->
                <td class="px-5 py-4 text-center">
                    <x-ui.status-badge :status="$customer->account_status" />
                </td>

                <!-- 4. ORDERS (Mono bold) -->
                <td class="px-5 py-4 text-center font-mono font-bold text-white text-xs tabular-nums">
                    {{ $customer->orders_count }}
                </td>

                <!-- 5. LIFETIME SPEND (Mono bold) -->
                <td class="px-5 py-4 text-right tabular-nums font-mono font-bold text-white text-xs">
                    ${{ number_format((float)($customer->lifetime_spend ?? 0), 2) }}
                </td>

                <!-- 6. REGISTERED (Mono) -->
                <td class="px-5 py-4 font-mono text-zinc-400 text-xs">
                    <x-ui.time :value="$customer->created_at" format="d M Y" />
                </td>

                <!-- 7. ACTIONS -->
                <td class="px-5 py-4 text-right">
                    <x-ui.button 
                        :href="route('admin.customers.show', $customer)" 
                        variant="secondary" 
                        size="xs"
                        class="font-mono text-xs"
                    >
                        <span>View</span>
                        <span>&rarr;</span>
                    </x-ui.button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="py-16 px-6 text-center">
                    <div class="w-12 h-12 mx-auto rounded-full bg-zinc-900 border border-white/[0.08] flex items-center justify-center text-admin-muted mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-white font-mono">No Customer Accounts Found</h3>
                    <p class="text-xs text-zinc-400 mt-1 max-w-sm mx-auto">
                        No customer records matched your query criteria.
                    </p>
                    <div class="mt-4">
                        <a 
                            href="{{ route('admin.customers.index') }}" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold font-mono bg-zinc-900 text-hoyos-red hover:text-white hover:bg-hoyos-red transition-all border border-white/[0.08]"
                        >
                            <span>Clear Filter Criteria</span>
                        </a>
                    </div>
                </td>
            </tr>
        @endforelse

        <x-slot:footer>
            <x-ui.pagination :paginator="$customers" />
        </x-slot:footer>
    </x-ui.data-table>
</div>
@endsection
