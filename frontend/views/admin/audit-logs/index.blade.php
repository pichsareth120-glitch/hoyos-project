@extends('layouts.admin')

@section('title', 'System Audit Logs')

@section('content')
<div class="space-y-6">
    <!-- 1. PageHeader Section -->
    <x-ui.page-header
        eyebrow="Security & Compliance"
        title="System Audit Logs"
        subtitle="Read-only immutable trail of administrative operations, catalog adjustments, and staff mutations."
    >
        <x-slot:status>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-mono bg-zinc-900/90 border border-white/[0.08] text-zinc-300">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Total Entries: <strong class="text-white font-mono">{{ $logs->total() }}</strong></span>
            </span>
        </x-slot:status>
    </x-ui.page-header>

    @php
        $totalEntries = (int) ($auditStats->total_entries ?? 0);
        $todayEntries = (int) ($auditStats->today_entries ?? 0);
        $distinctAdmins = (int) ($auditStats->distinct_admins ?? 0);
        $distinctEntities = (int) ($auditStats->distinct_entities ?? 0);

        $hasActiveFilters = request()->hasAny(['module', 'action', 'admin_id', 'target_table', 'target_id', 'date_from', 'date_to']);
    @endphp

    <!-- 2. KPI StatCards Row (2 cols on mobile <640px, 4 cols on desktop) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- 1. Total Entries -->
        <a href="{{ route('admin.audit-logs.index') }}" class="group block h-full">
            <x-ui.stat-card
                title="Total Entries"
                :value="$totalEntries"
                description="Recorded audit trail events"
                class="h-full group-hover:border-white/[0.2] transition-all cursor-pointer"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>
        </a>

        <!-- 2. Today's Activity -->
        <div class="block h-full">
            <x-ui.stat-card
                title="Today's Activity"
                :value="$todayEntries"
                description="Mutations logged today"
                class="h-full"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>
        </div>

        <!-- 3. Distinct Admins -->
        <div class="block h-full">
            <x-ui.stat-card
                title="Distinct Admins"
                :value="$distinctAdmins"
                description="Active initiating actors"
                class="h-full"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>
        </div>

        <!-- 4. Entity Types -->
        <div class="block h-full">
            <x-ui.stat-card
                title="Entity Types"
                :value="$distinctEntities"
                description="Monitored database models"
                class="h-full"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-hoyos-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>
        </div>
    </div>

    <!-- 3. FilterBar Section -->
    <x-ui.filter-bar
        :action="route('admin.audit-logs.index')"
        method="GET"
        :search="false"
        :reset-url="route('admin.audit-logs.index')"
        :has-active-filters="$hasActiveFilters"
    >
        <!-- Actor / Initiator Select -->
        <div class="w-full sm:w-44 flex-shrink-0">
            <select 
                name="admin_id" 
                class="w-full bg-zinc-950/80 border border-white/[0.1] text-zinc-300 text-xs rounded-xl px-3 py-2 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all font-mono"
            >
                <option value="" class="bg-zinc-900 text-zinc-300">All Initiators</option>
                <option value="system" class="bg-zinc-900 text-zinc-300" {{ request('admin_id') === 'system' ? 'selected' : '' }}>System (Automated)</option>
                @foreach($admins as $adm)
                    <option value="{{ $adm->id }}" class="bg-zinc-900 text-zinc-300" {{ request('admin_id') == $adm->id ? 'selected' : '' }}>
                        {{ $adm->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Action Verb Select / Input -->
        <div class="w-full sm:w-36 flex-shrink-0">
            <select 
                name="action" 
                class="w-full bg-zinc-950/80 border border-white/[0.1] text-zinc-300 text-xs rounded-xl px-3 py-2 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all font-mono"
            >
                <option value="" class="bg-zinc-900 text-zinc-300">All Actions</option>
                @foreach($actions as $act)
                    <option value="{{ $act }}" class="bg-zinc-900 text-zinc-300" {{ request('action') === $act ? 'selected' : '' }}>
                        {{ \App\Support\LabelHelper::humanize($act) }}
                    </option>
                @endforeach
                @if(request('action') && !$actions->contains(request('action')))
                    <option value="{{ request('action') }}" selected class="bg-zinc-900 text-zinc-300">{{ \App\Support\LabelHelper::humanize(request('action')) }}</option>
                @endif
            </select>
        </div>

        <!-- Module Select -->
        <div class="w-full sm:w-36 flex-shrink-0">
            <select 
                name="module" 
                class="w-full bg-zinc-950/80 border border-white/[0.1] text-zinc-300 text-xs rounded-xl px-3 py-2 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all font-mono"
            >
                <option value="" class="bg-zinc-900 text-zinc-300">All Modules</option>
                @foreach($modules as $mod)
                    <option value="{{ $mod }}" class="bg-zinc-900 text-zinc-300" {{ request('module') === $mod ? 'selected' : '' }}>
                        {{ \App\Support\LabelHelper::humanize($mod) }}
                    </option>
                @endforeach
                @if(request('module') && !$modules->contains(request('module')))
                    <option value="{{ request('module') }}" selected class="bg-zinc-900 text-zinc-300">{{ \App\Support\LabelHelper::humanize(request('module')) }}</option>
                @endif
            </select>
        </div>

        <!-- Entity Type Select (target_table) -->
        <div class="w-full sm:w-40 flex-shrink-0">
            <select 
                name="target_table" 
                class="w-full bg-zinc-950/80 border border-white/[0.1] text-zinc-300 text-xs rounded-xl px-3 py-2 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all font-mono"
            >
                <option value="" class="bg-zinc-900 text-zinc-300">All Entities</option>
                @foreach($entityTypes as $etype)
                    <option value="{{ $etype }}" class="bg-zinc-900 text-zinc-300" {{ request('target_table') === $etype ? 'selected' : '' }}>
                        {{ \App\Support\LabelHelper::humanize($etype) }}
                    </option>
                @endforeach
                @if(request('target_table') && !$entityTypes->contains(request('target_table')))
                    <option value="{{ request('target_table') }}" selected class="bg-zinc-900 text-zinc-300">{{ \App\Support\LabelHelper::humanize(request('target_table')) }}</option>
                @endif
            </select>
        </div>

        <!-- Entity ID Input (target_id) -->
        <div class="w-full sm:w-28 flex-shrink-0">
            <input 
                type="number" 
                name="target_id" 
                value="{{ request('target_id') }}" 
                placeholder="ID #" 
                class="w-full px-3 py-2 text-xs font-mono rounded-xl bg-white/[0.03] border border-white/[0.1] text-zinc-100 placeholder:text-admin-muted focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all"
            />
        </div>

        <!-- Date From Input -->
        <div class="w-full sm:w-36 flex-shrink-0">
            <input 
                type="date" 
                name="date_from" 
                value="{{ request('date_from') }}" 
                class="w-full px-3 py-2 text-xs font-mono rounded-xl bg-white/[0.03] border border-white/[0.1] text-zinc-300 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all"
            />
        </div>

        <!-- Date To Input -->
        <div class="w-full sm:w-36 flex-shrink-0">
            <input 
                type="date" 
                name="date_to" 
                value="{{ request('date_to') }}" 
                class="w-full px-3 py-2 text-xs font-mono rounded-xl bg-white/[0.03] border border-white/[0.1] text-zinc-300 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all"
            />
        </div>
    </x-ui.filter-bar>

    <!-- 4. DataTable Card with correctly aligned thead and shared Pagination inside -->
    <x-ui.data-table
        :empty="$logs->isEmpty()"
        emptyTitle="No Audit Logs Found"
        emptyDescription="No immutable audit records matched your current query parameters."
        emptyActionText="Clear Filter Criteria"
        :emptyActionHref="route('admin.audit-logs.index')"
    >
        <x-slot:thead>
            <tr>
                <th class="px-5 py-3.5 font-mono">TIMESTAMP</th>
                <th class="px-5 py-3.5 font-mono">ACTOR</th>
                <th class="px-4 py-3.5 font-mono">MODULE</th>
                <th class="px-4 py-3.5 font-mono">ACTION</th>
                <th class="px-5 py-3.5 font-mono">TARGET ENTITY</th>
                <th class="px-4 py-3.5 font-mono">IP ADDRESS</th>
                <th class="px-5 py-3.5 text-right font-mono">DETAILS</th>
            </tr>
        </x-slot:thead>

        @foreach($logs as $log)
            <tr class="hover:bg-white/[0.02] transition-colors">
                <!-- 1. TIMESTAMP (Mono) -->
                <td class="px-5 py-4 font-mono text-zinc-300 text-xs whitespace-nowrap">
                    <div class="text-white"><x-ui.time :value="$log->created_at" format="Y-m-d H:i:s" /></div>
                    <div class="text-[10px] text-admin-muted mt-0.5">{{ \App\Support\Time::diffForHumans($log->created_at) }}</div>
                </td>

                <!-- 2. ACTOR (Name in sans-serif, ID/System in mono) -->
                <td class="px-5 py-4">
                    @if($log->admin)
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-zinc-800 border border-zinc-700 flex items-center justify-center font-mono text-[10px] text-zinc-300 shrink-0 font-bold">
                                {{ strtoupper(substr($log->admin->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="font-medium text-white text-xs truncate font-sans">{{ $log->admin->name }}</div>
                                <div class="text-[10px] font-mono text-admin-muted block mt-0.5">Admin #{{ $log->admin_id }}</div>
                            </div>
                        </div>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-zinc-900 text-zinc-400 border border-zinc-800">
                            ⚙ System
                        </span>
                    @endif
                </td>

                <!-- 3. MODULE (Mono) -->
                <td class="px-4 py-4 font-mono">
                    <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase font-bold tracking-wider bg-zinc-900 border border-zinc-800 text-zinc-300 inline-block">
                        {{ $log->module }}
                    </span>
                </td>

                <!-- 4. ACTION (Neutral StatusBadge: create=green, update=amber, delete/archive=red, other=neutral) -->
                <td class="px-4 py-4 font-mono">
                    <x-ui.status-badge :status="$log->action" size="xs" />
                </td>

                <!-- 5. TARGET ENTITY (Mono IDs, links if known) -->
                <td class="px-5 py-4 font-mono text-xs">
                    @php
                        $humanEntity = \App\Support\LabelHelper::humanize($log->target_table);
                    @endphp
                    @if($log->target_table === 'orders' && $log->target_id)
                        <a href="{{ route('admin.orders.show', $log->target_id) }}" class="text-hoyos-red hover:underline inline-flex items-center gap-1 font-bold">
                            <span>{{ $humanEntity }} #{{ $log->target_id }}</span>
                            <span class="text-[10px] text-zinc-500">↗</span>
                        </a>
                    @elseif($log->target_table === 'users' && $log->target_id)
                        <a href="{{ route('admin.customers.show', $log->target_id) }}" class="text-hoyos-red hover:underline inline-flex items-center gap-1 font-bold">
                            <span>{{ $humanEntity }} #{{ $log->target_id }}</span>
                            <span class="text-[10px] text-zinc-500">↗</span>
                        </a>
                    @elseif($log->target_table === 'products' && $log->target_id)
                        <a href="{{ route('admin.products.edit', $log->target_id) }}" class="text-hoyos-red hover:underline inline-flex items-center gap-1 font-bold">
                            <span>{{ $humanEntity }} #{{ $log->target_id }}</span>
                            <span class="text-[10px] text-zinc-500">↗</span>
                        </a>
                    @elseif($log->target_table === 'product_variants' && $log->target_id)
                        <a href="{{ route('admin.inventory.show', $log->target_id) }}" class="text-hoyos-red hover:underline inline-flex items-center gap-1 font-bold">
                            <span>{{ $humanEntity }} #{{ $log->target_id }}</span>
                            <span class="text-[10px] text-zinc-500">↗</span>
                        </a>
                    @elseif($log->target_table === 'payments' && $log->target_id)
                        <a href="{{ route('admin.payments.index') }}" class="text-hoyos-red hover:underline font-bold">
                            <span>{{ $humanEntity }} #{{ $log->target_id }}</span>
                        </a>
                    @elseif($log->target_table)
                        <span class="text-zinc-300 font-mono">{{ $humanEntity }} #{{ $log->target_id }}</span>
                    @else
                        <span class="text-zinc-400 font-mono">—</span>
                    @endif
                </td>

                <!-- 6. IP ADDRESS (Mono) -->
                <td class="px-4 py-4 font-mono text-zinc-400 text-[11px]">
                    {{ $log->ip_address ?: '—' }}
                </td>

                <!-- 7. DETAILS (Button) -->
                <td class="px-5 py-4 text-right">
                    <x-ui.button 
                        :href="route('admin.audit-logs.show', $log)" 
                        variant="secondary" 
                        size="xs"
                        class="font-mono text-xs"
                    >
                        <span>Diff</span>
                        <span>&rarr;</span>
                    </x-ui.button>
                </td>
            </tr>
        @endforeach

        <x-slot:footer>
            <x-ui.pagination :paginator="$logs" />
        </x-slot:footer>
    </x-ui.data-table>
</div>
@endsection
