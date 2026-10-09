@extends('layouts.admin')

@section('title', 'Audit Log #' . $log->id)

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Top Bar Navigation & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-5 border-b border-white/[0.08]">
        <div>
            <a href="{{ route('admin.audit-logs.index') }}" class="inline-flex items-center gap-1.5 text-xs font-mono text-zinc-400 hover:text-white transition-colors mb-2">
                <span>&larr;</span>
                <span>Back to Audit Logs</span>
            </a>
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-hoyos-red"></span>
                <span class="text-[11px] font-mono uppercase tracking-wider text-hoyos-red font-semibold">Security Audit Record #{{ $log->id }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-white font-display uppercase">
                    {{ \App\Support\LabelHelper::humanize($log->module) }}: {{ \App\Support\LabelHelper::humanize($log->action) }}
                </h1>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider bg-zinc-900 text-zinc-400 border border-white/[0.08]">
                    Immutable Entry
                </span>
            </div>
        </div>

        <div class="flex items-center gap-3 font-mono text-xs text-zinc-400">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-zinc-900 border border-white/[0.08] text-zinc-300">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                <span>Recorded: <x-ui.time :value="$log->created_at" format="Y-m-d H:i:s" /></span>
            </span>
        </div>
    </div>

    <!-- 1-3. GlassCard Sections (Event, Actor, Entity) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
        <!-- Section 1: Event -->
        <x-ui.glass-card title="Event Details" description="Execution parameters and timing">
            <div class="space-y-3 text-xs font-mono">
                <div class="flex items-center justify-between pb-2 border-b border-white/[0.06]">
                    <span class="text-admin-muted uppercase text-[10px] font-bold">Action Type</span>
                    <x-ui.status-badge :status="$log->action" size="xs" />
                </div>
                <div class="flex items-center justify-between pb-2 border-b border-white/[0.06]">
                    <span class="text-admin-muted uppercase text-[10px] font-bold">Module</span>
                    <span class="text-white font-bold uppercase">{{ $log->module }}</span>
                </div>
                <div class="flex items-center justify-between pb-2 border-b border-white/[0.06]">
                    <span class="text-admin-muted uppercase text-[10px] font-bold">Client IP</span>
                    <span class="text-zinc-200">{{ $log->ip_address ?: '127.0.0.1' }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-admin-muted uppercase text-[10px] font-bold">Relative Time</span>
                    <span class="text-zinc-400 text-[11px]">{{ \App\Support\Time::diffForHumans($log->created_at) }}</span>
                </div>
            </div>
        </x-ui.glass-card>

        <!-- Section 2: Actor -->
        <x-ui.glass-card title="Initiating Actor" description="User identity behind mutation">
            <div class="space-y-3 text-xs">
                <div class="flex items-center justify-between pb-2 border-b border-white/[0.06]">
                    <span class="text-admin-muted uppercase text-[10px] font-mono font-bold">Name</span>
                    <span class="text-white font-bold font-sans">
                        {{ $log->admin ? $log->admin->name : 'System (Automated)' }}
                    </span>
                </div>
                <div class="flex items-center justify-between pb-2 border-b border-white/[0.06]">
                    <span class="text-admin-muted uppercase text-[10px] font-mono font-bold">Identifier</span>
                    <span class="text-zinc-300 font-mono">
                        {{ $log->admin_id ? 'Admin #' . $log->admin_id : 'Automated Worker' }}
                    </span>
                </div>
                <div class="flex items-center justify-between pb-2 border-b border-white/[0.06]">
                    <span class="text-admin-muted uppercase text-[10px] font-mono font-bold">Email</span>
                    <span class="text-zinc-300 font-mono text-[11px] truncate max-w-[180px]">
                        {{ $log->admin ? $log->admin->email : 'internal@hoyos.sys' }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-admin-muted uppercase text-[10px] font-mono font-bold">Role</span>
                    <span class="text-zinc-400 font-mono text-[11px] uppercase">
                        {{ $log->admin ? $log->admin->role : 'System' }}
                    </span>
                </div>
            </div>
        </x-ui.glass-card>

        <!-- Section 3: Entity -->
        <x-ui.glass-card title="Target Entity" description="Database resource impacted">
            <div class="space-y-3 text-xs font-mono">
                @php
                    $humanEntity = \App\Support\LabelHelper::humanize($log->target_table);
                @endphp
                <div class="flex items-center justify-between pb-2 border-b border-white/[0.06]">
                    <span class="text-admin-muted uppercase text-[10px] font-bold">Database Table</span>
                    <span class="text-white font-bold">{{ $humanEntity ?: 'None' }}</span>
                </div>
                <div class="flex items-center justify-between pb-2 border-b border-white/[0.06]">
                    <span class="text-admin-muted uppercase text-[10px] font-bold">Record ID</span>
                    <span class="text-zinc-300">{{ $log->target_id ? '#' . $log->target_id : 'N/A' }}</span>
                </div>
                <div class="flex items-center justify-between pt-1">
                    <span class="text-admin-muted uppercase text-[10px] font-bold">Quick Navigation</span>
                    <div>
                        @if($log->target_table === 'orders' && $log->target_id)
                            <a href="{{ route('admin.orders.show', $log->target_id) }}" class="text-hoyos-red hover:underline inline-flex items-center gap-1 font-bold">
                                <span>{{ $humanEntity }} #{{ $log->target_id }}</span>
                                <span class="text-[10px]">&rarr;</span>
                            </a>
                        @elseif($log->target_table === 'users' && $log->target_id)
                            <a href="{{ route('admin.customers.show', $log->target_id) }}" class="text-hoyos-red hover:underline inline-flex items-center gap-1 font-bold">
                                <span>{{ $humanEntity }} #{{ $log->target_id }}</span>
                                <span class="text-[10px]">&rarr;</span>
                            </a>
                        @elseif($log->target_table === 'products' && $log->target_id)
                            <a href="{{ route('admin.products.edit', $log->target_id) }}" class="text-hoyos-red hover:underline inline-flex items-center gap-1 font-bold">
                                <span>{{ $humanEntity }} #{{ $log->target_id }}</span>
                                <span class="text-[10px]">&rarr;</span>
                            </a>
                        @elseif($log->target_table === 'product_variants' && $log->target_id)
                            <a href="{{ route('admin.inventory.show', $log->target_id) }}" class="text-hoyos-red hover:underline inline-flex items-center gap-1 font-bold">
                                <span>{{ $humanEntity }} #{{ $log->target_id }}</span>
                                <span class="text-[10px]">&rarr;</span>
                            </a>
                        @elseif($log->target_table === 'payments')
                            <a href="{{ route('admin.payments.index') }}" class="text-hoyos-red hover:underline font-bold">
                                <span>{{ $humanEntity }} #{{ $log->target_id }}</span>
                            </a>
                        @else
                            <span class="text-admin-muted">—</span>
                        @endif
                    </div>
                </div>
            </div>
        </x-ui.glass-card>
    </div>

    <!-- Section 4: Changes (Attribute State Changes Diff) -->
    <x-ui.glass-card class="p-0 overflow-hidden">
        <div class="p-5 border-b border-white/[0.08] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-hoyos-red"></span>
                    <h3 class="text-xs font-mono font-bold uppercase tracking-wider text-white">
                        Attribute State Changes
                    </h3>
                </div>
                <p class="text-xs text-zinc-400 mt-0.5">
                    Side-by-side comparison of old vs new values. Removed values in red, added in green.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-[10px] font-mono">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/25">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    Added / New
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/25">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                    Removed / Old
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-zinc-900 text-zinc-400 border border-white/[0.08]">
                    <span class="w-1.5 h-1.5 rounded-full bg-zinc-500"></span>
                    Unchanged
                </span>
            </div>
        </div>

        @if(count($diff) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-zinc-950/60 border-b border-white/[0.08] text-[10px] font-mono uppercase tracking-wider text-zinc-400">
                            <th class="px-5 py-3 w-1/4">FIELD / KEY</th>
                            <th class="px-5 py-3 w-3/8">PREVIOUS (OLD VALUE)</th>
                            <th class="px-5 py-3 w-3/8">UPDATED (NEW VALUE)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04] font-mono">
                        @foreach($diff as $item)
                            <tr class="{{ $item['is_changed'] ? 'bg-white/[0.015] hover:bg-white/[0.03]' : 'hover:bg-white/[0.01]' }} transition-colors">
                                <!-- Field Key -->
                                <td class="px-5 py-3.5 align-top">
                                    <div class="flex items-center gap-2">
                                        @if($item['is_changed'])
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                            <span class="font-bold text-amber-300">{{ $item['key'] }}</span>
                                        @else
                                            <span class="w-1.5 h-1.5 rounded-full bg-zinc-600"></span>
                                            <span class="text-zinc-400">{{ $item['key'] }}</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Old Value (Removed in red if changed/removed) -->
                                <td class="px-5 py-3.5 align-top text-xs">
                                    @if($item['has_old'])
                                        @if(is_null($item['old_value']))
                                            <span class="text-admin-muted italic">null</span>
                                        @elseif(is_bool($item['old_value']))
                                            <span class="text-amber-400 font-bold">{{ $item['old_value'] ? 'true' : 'false' }}</span>
                                        @elseif(is_array($item['old_value']))
                                            <pre class="text-[11px] bg-zinc-950 p-2.5 rounded-lg border border-zinc-800 text-rose-300 whitespace-pre-wrap break-all">{{ json_encode($item['old_value'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        @else
                                            @if($item['is_changed'])
                                                <span class="inline-block px-2 py-0.5 rounded bg-rose-500/10 text-rose-300 border border-rose-500/25 line-through">
                                                    {{ (string) $item['old_value'] }}
                                                </span>
                                            @else
                                                <span class="text-zinc-300">
                                                    {{ (string) $item['old_value'] }}
                                                </span>
                                            @endif
                                        @endif
                                    @else
                                        <span class="text-zinc-400 italic">— (not present)</span>
                                    @endif
                                </td>

                                <!-- New Value (Added in green if changed/added) -->
                                <td class="px-5 py-3.5 align-top text-xs">
                                    @if($item['has_new'])
                                        @if(is_null($item['new_value']))
                                            <span class="text-zinc-400 italic">null</span>
                                        @elseif(is_bool($item['new_value']))
                                            <span class="text-amber-400 font-bold">{{ $item['new_value'] ? 'true' : 'false' }}</span>
                                        @elseif(is_array($item['new_value']))
                                            <pre class="text-[11px] bg-zinc-950 p-2.5 rounded-lg border border-zinc-800 text-emerald-300 whitespace-pre-wrap break-all">{{ json_encode($item['new_value'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        @else
                                            @if($item['is_changed'])
                                                <span class="inline-block px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-300 border border-emerald-500/25 font-bold">
                                                    {{ (string) $item['new_value'] }}
                                                </span>
                                            @else
                                                <span class="text-zinc-300">
                                                    {{ (string) $item['new_value'] }}
                                                </span>
                                            @endif
                                        @endif
                                    @else
                                        <span class="inline-block px-2 py-0.5 rounded bg-rose-500/10 text-rose-400 border border-rose-500/25 italic">
                                            — (removed)
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-8 text-center text-zinc-400 text-xs font-mono">
                No structured attribute key-value pairs recorded for this action.
            </div>
        @endif
    </x-ui.glass-card>

    <!-- Raw JSON Snapshots Side-by-Side (Horizontally scrollable on mobile) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Old Values Card -->
        <x-ui.glass-card title="Old Values (JSON Payload)" description="State snapshot prior to execution (sensitive keys masked).">
            <div class="bg-zinc-950 font-mono text-xs p-4 rounded-xl border border-white/[0.08] text-rose-300/80 overflow-x-auto max-h-96">
                <pre class="leading-relaxed"><code>{{ json_encode($oldValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
            </div>
        </x-ui.glass-card>

        <!-- New Values Card -->
        <x-ui.glass-card title="New Values (JSON Payload)" description="State snapshot after successful execution.">
            <div class="bg-zinc-950 font-mono text-xs p-4 rounded-xl border border-white/[0.08] text-emerald-300/80 overflow-x-auto max-h-96">
                <pre class="leading-relaxed"><code>{{ json_encode($newValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
            </div>
        </x-ui.glass-card>
    </div>
</div>
@endsection
