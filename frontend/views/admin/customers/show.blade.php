@extends('layouts.admin')

@section('title', 'Customer: ' . $customer->name)

@section('content')
<div 
    class="space-y-6 max-w-7xl mx-auto" 
    x-data="{ 
        suspendModalOpen: {{ $errors->has('reason') ? 'true' : 'false' }}, 
        activateModalOpen: false 
    }"
>
    <!-- Top Breadcrumb & Header Navigation -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-white/[0.08]">
        <div>
            <a href="{{ route('admin.customers.index') }}" class="inline-flex items-center gap-1.5 text-xs font-mono text-zinc-400 hover:text-white transition-colors mb-2">
                <span>←</span>
                <span>Back to Customer Accounts</span>
            </a>
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-hoyos-red"></span>
                <span class="text-[11px] font-mono uppercase tracking-wider text-hoyos-red font-semibold">Client Identity</span>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-2xl font-bold tracking-tight text-white font-display uppercase">
                    {{ $customer->name }}
                </h1>
                <x-ui.status-badge :status="$customer->account_status" />
            </div>
        </div>
    </div>

    <!-- Sticky ActionBar for Suspend / Activate Actions -->
    <x-ui.action-bar class="sticky top-16 z-30 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-mono bg-zinc-900 border border-white/[0.08] text-zinc-300">
                <span class="w-1.5 h-1.5 rounded-full {{ $customer->account_status === 'active' ? 'bg-emerald-400' : 'bg-rose-400' }}"></span>
                <span class="truncate">Customer ID #{{ $customer->id }}</span>
            </span>
            <span class="text-xs font-sans text-zinc-400 hidden md:inline truncate">{{ $customer->name }}</span>
        </div>
        <div class="flex items-center gap-2.5 shrink-0">
            <x-ui.button 
                :href="route('admin.customers.index')" 
                variant="secondary" 
                size="sm"
                class="font-sans"
            >
                Back
            </x-ui.button>

            @if($customer->account_status === 'active')
                <x-ui.button 
                    id="open-suspend-modal-btn"
                    type="button" 
                    @click="suspendModalOpen = true"
                    variant="danger" 
                    size="sm"
                    class="font-sans"
                >
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                    <span>Suspend Account</span>
                </x-ui.button>
            @else
                <x-ui.button 
                    type="button" 
                    @click="activateModalOpen = true"
                    variant="primary" 
                    size="sm"
                    class="font-sans"
                >
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Activate Account</span>
                </x-ui.button>
            @endif
        </div>
    </x-ui.action-bar>

    <!-- Section 1: Customer Profile Overview GlassCard -->
    <x-ui.glass-card>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            @php
                $words = explode(' ', trim($customer->name));
                $initials = count($words) >= 2 
                    ? strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1)) 
                    : strtoupper(substr($customer->name, 0, 2));
            @endphp
            <div class="flex items-start sm:items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-zinc-800 text-zinc-200 font-mono text-lg flex items-center justify-center border border-zinc-700 shrink-0 font-bold shadow-lg">
                    {{ $initials }}
                </div>
                <div class="space-y-1">
                    <h2 class="text-xl font-bold font-sans text-white">{{ $customer->name }}</h2>
                    <div class="flex flex-wrap items-center gap-3 text-xs font-mono text-zinc-400">
                        <span class="text-zinc-200">{{ $customer->email }}</span>
                        <span class="text-zinc-600">•</span>
                        <span>{{ $customer->phone ?: 'No phone registered' }}</span>
                        <span class="text-zinc-600">•</span>
                        <span>Member since: <strong class="text-zinc-200"><x-ui.time :value="$customer->created_at" format="M d, Y" /></strong></span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="px-3 py-1.5 rounded-xl font-mono text-xs border bg-zinc-950/80 border-white/[0.08] text-zinc-300">
                    User Role: <strong class="text-white uppercase">{{ $customer->role }}</strong>
                </span>
            </div>
        </div>
    </x-ui.glass-card>

    <!-- Section 2: Lifetime Spend & Order Metrics KPI Row -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
        <!-- 1. Lifetime Spend -->
        <x-ui.glass-card variant="subtle" class="!p-4 sm:!p-5">
            <div class="text-[10px] font-mono uppercase tracking-wider text-zinc-400 font-bold mb-1">
                Lifetime Spend (Paid)
            </div>
            <div class="text-2xl font-bold font-mono text-white tabular-nums">
                ${{ number_format($lifetimeSpend, 2) }}
            </div>
            <div class="text-[10px] font-mono text-admin-muted mt-1">
                Excludes refunded & cancelled orders
            </div>
        </x-ui.glass-card>

        <!-- 2. Total Orders -->
        <x-ui.glass-card variant="subtle" class="!p-4 sm:!p-5">
            <div class="text-[10px] font-mono uppercase tracking-wider text-zinc-400 font-bold mb-1">
                Total Orders Placed
            </div>
            <div class="text-2xl font-bold font-mono text-white tabular-nums">
                {{ $orderCount }}
            </div>
            <div class="text-[10px] font-mono text-admin-muted mt-1">
                All lifecycle order records
            </div>
        </x-ui.glass-card>

        <!-- 3. Average Order Value (AOV) -->
        <x-ui.glass-card variant="subtle" class="col-span-2 sm:col-span-1 !p-4 sm:!p-5">
            <div class="text-[10px] font-mono uppercase tracking-wider text-zinc-400 font-bold mb-1">
                Avg. Order Value (AOV)
            </div>
            <div class="text-2xl font-bold font-mono text-white tabular-nums">
                ${{ number_format($averageOrderValue, 2) }}
            </div>
            <div class="text-[10px] font-mono text-admin-muted mt-1">
                Average across paid orders
            </div>
        </x-ui.glass-card>
    </div>

    <!-- Section 3: Customer Order History GlassCard -->
    <x-ui.glass-card>
        <x-slot:header>
            <div class="flex items-center justify-between gap-4 border-b border-white/[0.08] pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-hoyos-red"></span>
                        <h3 class="text-xs font-mono font-semibold tracking-wider text-zinc-200 uppercase">
                            Order History
                        </h3>
                    </div>
                    <p class="text-xs text-zinc-400 font-sans leading-relaxed mt-0.5">
                        Historical purchasing records and fulfillment statuses for this client account.
                    </p>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-mono text-zinc-400 bg-zinc-900 border border-white/[0.08] shrink-0">
                    {{ $orders->total() }} {{ Str::plural('order', $orders->total()) }}
                </span>
            </div>
        </x-slot:header>

        @if($orders->isEmpty())
            <div class="py-12 text-center">
                <div class="w-10 h-10 mx-auto rounded-full bg-zinc-900 border border-white/[0.08] flex items-center justify-center text-admin-muted mb-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <p class="text-xs font-mono uppercase text-zinc-400">No Orders Placed Yet</p>
                <p class="text-[11px] font-sans text-admin-muted mt-0.5">This customer has not completed any orders in the store.</p>
            </div>
        @else
            <div class="overflow-x-auto -mx-6 -my-4">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-zinc-950/60 border-b border-white/[0.08] text-[10px] font-mono uppercase tracking-wider text-zinc-400">
                            <th class="px-5 py-3 font-mono">ORDER #</th>
                            <th class="px-5 py-3 font-mono">DATE</th>
                            <th class="px-5 py-3 font-mono text-center">ORDER STATUS</th>
                            <th class="px-5 py-3 font-mono text-center">PAYMENT</th>
                            <th class="px-5 py-3 font-mono text-center">ITEMS</th>
                            <th class="px-5 py-3 font-mono text-right">TOTAL</th>
                            <th class="px-5 py-3 font-mono text-right">ACTION</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        @foreach($orders as $order)
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="px-5 py-3.5 font-mono text-xs font-bold text-white whitespace-nowrap">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="text-hoyos-red hover:underline">
                                        #{{ $order->order_number }}
                                    </a>
                                </td>
                                <td class="px-5 py-3.5 font-mono text-xs text-zinc-400 whitespace-nowrap">
                                    <x-ui.time :value="$order->created_at" format="d M Y" />
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <x-ui.status-badge :status="$order->order_status" />
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <x-ui.status-badge :status="$order->payment_status" />
                                </td>
                                <td class="px-5 py-3.5 text-center font-mono text-xs text-zinc-300">
                                    {{ $order->items_count }}
                                </td>
                                <td class="px-5 py-3.5 text-right font-mono text-xs font-bold text-white tabular-nums">
                                    ${{ number_format($order->total_amount, 2) }}
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <x-ui.button 
                                        :href="route('admin.orders.show', $order)" 
                                        variant="secondary" 
                                        size="xs"
                                        class="font-mono text-xs"
                                    >
                                        <span>View</span>
                                        <span>&rarr;</span>
                                    </x-ui.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="mt-4 pt-4 border-t border-white/[0.06]">
                    <x-ui.pagination :paginator="$orders" />
                </div>
            @endif
        @endif
    </x-ui.glass-card>

    <!-- Section 4: Shipping Addresses GlassCard -->
    <x-ui.glass-card>
        <x-slot:header>
            <div class="flex items-center justify-between gap-4 border-b border-white/[0.08] pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-hoyos-red"></span>
                        <h3 class="text-xs font-mono font-semibold tracking-wider text-zinc-200 uppercase">
                            Saved Shipping Addresses
                        </h3>
                    </div>
                    <p class="text-xs text-zinc-400 font-sans leading-relaxed mt-0.5">
                        Client delivery destinations registered on file.
                    </p>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-mono text-zinc-400 bg-zinc-900 border border-white/[0.08] shrink-0">
                    {{ $addresses->count() }} {{ Str::plural('address', $addresses->count()) }}
                </span>
            </div>
        </x-slot:header>

        @if($addresses->isEmpty())
            <div class="py-8 text-center">
                <div class="w-9 h-9 mx-auto rounded-full bg-zinc-900 border border-white/[0.08] flex items-center justify-center text-admin-muted mb-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <p class="text-xs font-mono uppercase text-zinc-400">No Saved Addresses</p>
                <p class="text-[11px] font-sans text-admin-muted mt-0.5">This customer has not recorded any delivery addresses.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($addresses as $addr)
                    <div class="p-4 rounded-xl border border-white/[0.08] bg-white/[0.02] space-y-2 relative">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-bold text-white font-sans">{{ $addr->recipient_name }}</span>
                            @if($addr->is_default)
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 font-bold">
                                    Default
                                </span>
                            @endif
                        </div>
                        <div class="text-xs font-mono text-zinc-400">{{ $addr->phone }}</div>
                        <div class="text-xs font-sans text-zinc-300 leading-relaxed">
                            {{ $addr->address_line_1 }}
                            @if($addr->address_line_2)
                                <br>{{ $addr->address_line_2 }}
                            @endif
                        </div>
                        <div class="text-xs font-mono text-zinc-400">
                            {{ $addr->city ?? $addr->province }}, Cambodia
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.glass-card>

    <!-- ConfirmModal: Account Suspension (variant="danger") -->
    <x-ui.confirm-modal
        name="suspendModalOpen"
        title="Confirm Account Suspension"
        description="Suspending {{ $customer->name }} will immediately block login access and {{ config('session.driver') === 'database' ? 'terminate active sessions' : 'existing sessions will be blocked on their next request' }}. Historical orders and data will remain intact."
        action="{{ route('admin.customers.suspend', $customer) }}"
        method="POST"
        confirm-text="Suspend Account"
        variant="danger"
    >
        <x-ui.form-field
            label="Suspension Reason"
            name="reason"
            :required="true"
            helper="Minimum 5 characters required for administrative audit trail."
        >
            <textarea
                name="reason"
                id="reason"
                rows="3"
                required
                minlength="5"
                placeholder="Provide reason for account suspension (e.g. Terms violation, chargeback fraud, customer request)..."
                class="w-full bg-white/[0.03] border border-white/[0.1] focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red rounded-xl p-3 text-xs font-sans text-zinc-100 placeholder:text-admin-muted focus:outline-none transition-all"
            >{{ old('reason') }}</textarea>
        </x-ui.form-field>
    </x-ui.confirm-modal>

    <!-- ConfirmModal: Account Activation (variant="neutral") -->
    <x-ui.confirm-modal
        name="activateModalOpen"
        title="Confirm Account Activation"
        description="Restore active login permissions and checkout capabilities for {{ $customer->name }}."
        action="{{ route('admin.customers.activate', $customer) }}"
        method="POST"
        confirm-text="Activate Account"
        variant="neutral"
    />
</div>
@endsection
