<x-admin-layout title="Payments & Transactions">
    <div class="space-y-6" x-data="{ 
        refundModalOpen: {{ $errors->has('notes') ? 'true' : 'false' }}, 
        refundActionUrl: '{{ old('refund_action_url', '') }}', 
        refundPaymentId: '{{ old('refund_payment_id', '') }}', 
        refundOrderNum: '{{ old('refund_order_num', '') }}',
        openRefundModal(url, id, orderNum) {
            this.refundActionUrl = url;
            this.refundPaymentId = id;
            this.refundOrderNum = orderNum;
            this.refundModalOpen = true;
        }
    }">

        <!-- Header Section using shared PageHeader -->
        <x-ui.page-header
            eyebrow="Financial Ledger"
            title="Payments & Transactions"
            subtitle="Audit ABA KHQR payment attempts, verify COD settlements, and record off-platform refunds."
        >
            <x-slot:status>
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-mono bg-white/[0.04] border border-white/[0.1] text-zinc-300 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shadow-[0_0_8px_#34D399]"></span>
                    <span>Total Records: <strong class="text-white font-mono">{{ $payments->total() ?? count($payments) }}</strong></span>
                </span>
            </x-slot:status>
        </x-ui.page-header>

        <!-- KPI StatCards Row (Single Grouped Query, Equal Heights) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
            <!-- 1. Total Transactions -->
            <x-ui.stat-card
                title="Total Transactions"
                :value="$paymentCounts->total_payments ?? 0"
                subtext="Audit ledger records"
                :href="route('admin.payments.index')"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>

            <!-- 2. Paid & Settled -->
            <x-ui.stat-card
                title="Paid & Settled"
                :value="$paymentCounts->paid_count ?? 0"
                subtext="Completed settlements"
                :href="route('admin.payments.index', ['status' => 'paid'])"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>

            <!-- 3. Pending Verification -->
            <x-ui.stat-card
                title="Pending Verification"
                :value="$paymentCounts->pending_count ?? 0"
                subtext="Awaiting cash or scan"
                :href="route('admin.payments.index', ['status' => 'pending'])"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>

            <!-- 4. Refunded Records -->
            <x-ui.stat-card
                title="Refunded Records"
                :value="$paymentCounts->refunded_count ?? 0"
                subtext="Off-platform refund logs"
                :href="route('admin.payments.index', ['status' => 'refunded'])"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>
        </div>

        <!-- Quick Status Filter Pills (Wrap cleanly at 1280px) -->
        @php
            $quickStatusTabs = [
                ''          => 'ALL',
                'paid'      => 'PAID',
                'pending'   => 'PENDING',
                'refunded'  => 'REFUNDED',
                'expired'   => 'EXPIRED',
                'cancelled' => 'CANCELLED',
            ];
            $currentStatus = request('status', '');
            $hasActiveFilters = request()->hasAny(['search', 'status', 'method']);
        @endphp
        <div class="flex flex-wrap items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar">
            @foreach($quickStatusTabs as $val => $label)
                @php $isActive = $currentStatus === $val; @endphp
                <a 
                    href="{{ $val ? route('admin.payments.index', array_merge(request()->except('status'), ['status' => $val])) : route('admin.payments.index', request()->except('status')) }}"
                    class="{{ $isActive ? 'bg-hoyos-red text-white border-hoyos-red shadow-[0_0_16px_rgba(255,0,46,0.35)]' : 'text-zinc-300 hover:text-white bg-white/[0.03] hover:bg-white/[0.06] border border-white/[0.08] hover:border-white/[0.16]' }} max-sm:min-h-[40px] inline-flex items-center justify-center text-xs font-mono font-semibold px-3 py-1.5 sm:py-1.5 rounded-xl transition-all whitespace-nowrap"
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <!-- FilterBar Section (Wraps cleanly at 1280px) -->
        <x-ui.filter-bar
            :action="route('admin.payments.index')"
            method="GET"
            :search="request('search')"
            searchPlaceholder="Transaction ID, order #, customer name..."
            :hasActiveFilters="$hasActiveFilters"
            :resetUrl="route('admin.payments.index')"
        >
            <!-- Status Dropdown -->
            <div class="w-full sm:w-auto min-w-[170px] shrink-0">
                <select 
                    name="status" 
                    class="w-full py-2 px-3 text-xs rounded-xl bg-white/[0.03] border border-white/[0.1] text-zinc-300 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all font-mono"
                >
                    <option value="" class="bg-zinc-900 text-zinc-300">All Statuses</option>
                    <option value="paid" class="bg-zinc-900 text-zinc-300" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="pending" class="bg-zinc-900 text-zinc-300" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="refunded" class="bg-zinc-900 text-zinc-300" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                    <option value="expired" class="bg-zinc-900 text-zinc-300" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="cancelled" class="bg-zinc-900 text-zinc-300" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <!-- Method Dropdown -->
            <div class="w-full sm:w-auto min-w-[170px] shrink-0">
                <select 
                    name="method" 
                    class="w-full py-2 px-3 text-xs rounded-xl bg-white/[0.03] border border-white/[0.1] text-zinc-300 focus:outline-none focus:border-hoyos-red focus:ring-1 focus:ring-hoyos-red transition-all font-mono"
                >
                    <option value="" class="bg-zinc-900 text-zinc-300">All Methods</option>
                    <option value="aba_khqr" class="bg-zinc-900 text-zinc-300" {{ request('method') === 'aba_khqr' ? 'selected' : '' }}>ABA KHQR</option>
                    <option value="cod" class="bg-zinc-900 text-zinc-300" {{ request('method') === 'cod' ? 'selected' : '' }}>COD</option>
                </select>
            </div>
        </x-ui.filter-bar>

        <!-- Payments Ledger DataTable -->
        <x-ui.data-table
            :empty="$payments->isEmpty()"
            emptyTitle="No Payment Transactions Found"
            emptyDescription="Try adjusting your search criteria or resetting filters."
            emptyActionText="Clear Filter Criteria"
            :emptyActionHref="route('admin.payments.index')"
        >
            <x-slot:thead>
                <tr>
                    <th class="px-5 py-3.5 font-mono">PAYMENT ID</th>
                    <th class="px-5 py-3.5 font-mono">ORDER #</th>
                    <th class="px-5 py-3.5 font-sans">CUSTOMER</th>
                    <th class="px-4 py-3.5 font-mono">METHOD</th>
                    <th class="px-4 py-3.5 text-center font-sans">STATUS</th>
                    <th class="px-4 py-3.5 text-right font-mono">AMOUNT</th>
                    <th class="px-5 py-3.5 font-mono">TRANSACTION REF</th>
                    <th class="px-5 py-3.5 font-mono">SETTLED AT</th>
                    <th class="px-5 py-3.5 text-right font-sans">ACTIONS</th>
                </tr>
            </x-slot:thead>

            @foreach($payments as $payment)
                @php
                    $order = $payment->order;
                    $canMarkPaidCod = $payment->payment_method === 'cod'
                        && $payment->status === 'pending'
                        && $order
                        && $order->order_status !== 'cancelled'
                        && in_array($order->order_status, ['shipped', 'delivered'], true);
                    $canRefund = $payment->status === 'paid';
                @endphp
                <tr class="hover:bg-white/[0.02] transition-colors border-b border-white/[0.04]">
                    <!-- 1. PAYMENT ID -->
                    <td class="px-5 py-4 font-mono font-bold text-white text-xs">
                        #{{ $payment->id }}
                    </td>

                    <!-- 2. ORDER # -->
                    <td class="px-5 py-4">
                        @if($order)
                            <a 
                                href="{{ route('admin.orders.show', $order) }}" 
                                class="font-mono font-bold text-white hover:text-hoyos-red transition-colors inline-flex items-center gap-1 text-xs group"
                            >
                                <span class="group-hover:underline">#{{ $order->order_number }}</span>
                                <span class="text-[10px] text-zinc-500 group-hover:text-hoyos-red">↗</span>
                            </a>
                            <div class="mt-1">
                                <x-ui.status-badge :status="$order->order_status" size="sm" />
                            </div>
                        @else
                            <span class="text-zinc-400 italic text-xs font-sans">Orphan Payment</span>
                        @endif
                    </td>

                    <!-- 3. CUSTOMER -->
                    <td class="px-5 py-4">
                        @if($order)
                            <div class="font-medium text-white text-xs font-sans">{{ $order->customer_name }}</div>
                            <div class="text-[11px] font-mono text-zinc-400 mt-0.5">{{ $order->customer_phone }}</div>
                        @else
                            <span class="text-zinc-400 font-sans">—</span>
                        @endif
                    </td>

                    <!-- 4. METHOD -->
                    <td class="px-4 py-4">
                        <span class="font-mono text-xs text-zinc-300 bg-white/[0.04] border border-white/[0.08] px-2 py-0.5 rounded-lg inline-block">
                            {{ $payment->payment_method === 'aba_khqr' ? 'ABA KHQR' : 'COD' }}
                        </span>
                    </td>

                    <!-- 5. STATUS -->
                    <td class="px-4 py-4 text-center">
                        <x-ui.status-badge :status="$payment->status" />
                    </td>

                    <!-- 6. AMOUNT -->
                    <td class="px-4 py-4 text-right font-mono font-bold text-white text-xs tabular-nums">
                        ${{ number_format($payment->amount, 2) }}
                    </td>

                    <!-- 7. TRANSACTION REF -->
                    <td class="px-5 py-4 font-mono text-[11px] text-zinc-300">
                        @if($payment->transaction_id)
                            <span class="text-white bg-zinc-950/80 px-2 py-0.5 rounded-lg border border-white/[0.1] font-mono select-all">{{ $payment->transaction_id }}</span>
                        @else
                            <span class="text-zinc-400 italic font-sans">—</span>
                        @endif
                    </td>

                    <!-- 8. SETTLED AT -->
                    <td class="px-5 py-4 font-mono text-[11px]">
                        @if($payment->paid_at)
                            <div class="text-zinc-200 font-medium"><x-ui.time :value="$payment->paid_at" format="M d, Y H:i" /></div>
                            <div class="text-[10px] text-admin-muted font-sans">{{ \App\Support\Time::diffForHumans($payment->paid_at) }}</div>
                        @elseif($payment->expires_at)
                            <div class="text-zinc-400">Expires: <x-ui.time :value="$payment->expires_at" format="M d, H:i" /></div>
                            <div class="text-[10px] text-admin-muted font-sans">{{ \App\Support\Time::diffForHumans($payment->expires_at) }}</div>
                        @else
                            <div class="text-admin-muted"><x-ui.time :value="$payment->created_at" format="M d, Y H:i" /></div>
                        @endif
                    </td>

                    <!-- 9. ACTIONS -->
                    <td class="px-5 py-4 text-right">
                        <div class="inline-flex items-center gap-1.5 justify-end">
                            @if($canMarkPaidCod)
                                <form method="POST" action="{{ route('admin.payments.mark-paid', $payment) }}" class="inline">
                                    @csrf
                                    <x-ui.button 
                                        type="submit"
                                        variant="secondary"
                                        size="xs"
                                        class="font-sans text-emerald-400 border-emerald-500/30 hover:border-emerald-500/50 bg-emerald-500/10 hover:bg-emerald-500/20"
                                        onclick="return confirm('Confirm that COD cash has been collected for Order #{{ $order->order_number }}?')"
                                    >
                                        Mark Paid
                                    </x-ui.button>
                                </form>
                            @endif

                            @if($canRefund)
                                <x-ui.button 
                                    type="button"
                                    variant="secondary"
                                    size="xs"
                                    class="font-sans text-amber-400 border-amber-500/30 hover:border-amber-500/50 bg-amber-500/10 hover:bg-amber-500/20"
                                    @click="openRefundModal('{{ route('admin.payments.refund', $payment) }}', '{{ $payment->id }}', '{{ $order ? $order->order_number : 'N/A' }}')"
                                >
                                    Record Refund
                                </x-ui.button>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach

            <!-- Pagination Footer inside DataTable -->
            @if($payments->isNotEmpty())
                <x-slot:footer>
                    <x-ui.pagination :paginator="$payments" />
                </x-slot:footer>
            @endif
        </x-ui.data-table>

        <!-- Off-Platform Refund ConfirmModal -->
        <x-ui.confirm-modal
            name="refundModalOpen"
            title="Record Off-Platform Refund"
            actionDynamic="refundActionUrl"
            method="POST"
            confirmText="Record Refund"
            cancelText="Cancel"
            variant="primary"
        >
            <div class="p-3.5 bg-zinc-950/80 rounded-xl border border-white/[0.08] text-xs text-zinc-300 space-y-1.5 font-sans mb-4">
                <p class="font-bold text-white font-sans flex items-center justify-between">
                    <span>Payment #<strong class="font-mono text-zinc-200" x-text="refundPaymentId"></strong></span>
                    <span class="text-zinc-400 font-normal">Order #<strong class="font-mono text-zinc-200" x-text="refundOrderNum"></strong></span>
                </p>
                <p class="text-[11px] leading-relaxed text-amber-300/90 font-sans">
                    This only <strong>records an off-platform refund</strong> in Hoyos records.
                    It does <strong>not</strong> change the order status or inventory stock.
                </p>
            </div>

            <!-- Hidden fields to remember state across validation errors -->
            <input type="hidden" name="refund_action_url" :value="refundActionUrl" value="{{ old('refund_action_url') }}">
            <input type="hidden" name="refund_payment_id" :value="refundPaymentId" value="{{ old('refund_payment_id') }}">
            <input type="hidden" name="refund_order_num" :value="refundOrderNum" value="{{ old('refund_order_num') }}">

            <x-ui.form-field 
                label="Refund Reason & Reference Notes" 
                name="notes" 
                :required="true"
                helper="Minimum 5 characters required for audit trail."
            >
                <textarea 
                    name="notes" 
                    rows="3" 
                    required 
                    minlength="5"
                    placeholder="Enter bank refund transaction reference, customer note, or reason..."
                    class="w-full bg-zinc-950/80 border border-zinc-800 focus:border-hoyos-red rounded-xl p-3 text-xs text-zinc-100 placeholder:text-zinc-400 focus:outline-none focus:ring-1 focus:ring-hoyos-red transition-colors font-sans"
                >{{ old('notes') }}</textarea>
            </x-ui.form-field>
        </x-ui.confirm-modal>

    </div>
</x-admin-layout>
