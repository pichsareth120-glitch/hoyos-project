<?php

namespace App\Services\Reports;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Shipment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get high-level KPI cards for today and this month.
     */
    public function getMetrics(): array
    {
        $tz = DateBucketHelper::getTimezone();
        $now = Carbon::now($tz);

        // Local boundaries converted to UTC
        $todayStartUtc = DateBucketHelper::toUtc($now->copy()->startOfDay());
        $todayEndUtc = DateBucketHelper::toUtc($now->copy()->endOfDay());

        $monthStartUtc = DateBucketHelper::toUtc($now->copy()->startOfMonth()->startOfDay());
        $monthEndUtc = DateBucketHelper::toUtc($now->copy()->endOfMonth()->endOfDay());

        $lastMonthStartUtc = DateBucketHelper::toUtc($now->copy()->subMonthNoOverflow()->startOfMonth()->startOfDay());
        $lastMonthEndUtc = DateBucketHelper::toUtc($now->copy()->subMonthNoOverflow()->endOfMonth()->endOfDay());

        // Base revenue query closure
        $revenueCondition = function ($q) {
            $q->where('payment_status', 'paid')
              ->where('order_status', '<>', 'cancelled');
        };

        // Revenue Today
        $revenueToday = (string) (Order::where($revenueCondition)
            ->whereBetween('created_at', [$todayStartUtc, $todayEndUtc])
            ->sum('total_amount') ?? '0.00');

        // Revenue This Month & Paid Orders This Month
        $monthOrders = Order::where($revenueCondition)
            ->whereBetween('created_at', [$monthStartUtc, $monthEndUtc])
            ->selectRaw('COUNT(*) as paid_count, COALESCE(SUM(total_amount), 0) as total_rev')
            ->first();

        $revenueThisMonth = (string) ($monthOrders->total_rev ?? '0.00');
        $paidOrdersThisMonth = (int) ($monthOrders->paid_count ?? 0);

        // Revenue Last Month
        $revenueLastMonth = (string) (Order::where($revenueCondition)
            ->whereBetween('created_at', [$lastMonthStartUtc, $lastMonthEndUtc])
            ->sum('total_amount') ?? '0.00');

        // Orders Placed Today (non-cancelled)
        $ordersToday = Order::where('order_status', '<>', 'cancelled')
            ->whereBetween('created_at', [$todayStartUtc, $todayEndUtc])
            ->count();

        // AOV This Month
        $aovThisMonth = $paidOrdersThisMonth > 0
            ? bcdiv($revenueThisMonth, (string) $paidOrdersThisMonth, 2)
            : '0.00';

        // Month-over-month comparison percentage
        $lastMonthNum = (float) $revenueLastMonth;
        $thisMonthNum = (float) $revenueThisMonth;
        $momGrowth = null;
        if ($lastMonthNum > 0) {
            $momGrowth = round((($thisMonthNum - $lastMonthNum) / $lastMonthNum) * 100, 1);
        }

        return [
            'revenue_today' => DateBucketHelper::formatMoney($revenueToday),
            'revenue_this_month' => DateBucketHelper::formatMoney($revenueThisMonth),
            'revenue_last_month' => DateBucketHelper::formatMoney($revenueLastMonth),
            'mom_growth' => $momGrowth,
            'orders_today' => $ordersToday,
            'paid_orders_this_month' => $paidOrdersThisMonth,
            'aov_this_month' => DateBucketHelper::formatMoney($aovThisMonth),
        ];
    }

    /**
     * Get real-time administrative action queues with counts and matching filter URLs.
     */
    public function getActionQueues(): array
    {
        $lowStockThreshold = ProductVariant::lowStockThreshold();

        // 1. Orders awaiting processing (confirmed, and either COD or KHQR paid)
        $awaitingProcessingCount = Order::where('order_status', 'confirmed')
            ->readyForFulfillment()
            ->count();

        // 2. KHQR orders awaiting payment (payment_method aba_khqr, order_status confirmed, payment_status pending or expired)
        $khqrAwaitingPaymentCount = Order::where('payment_method', 'aba_khqr')
            ->where('order_status', 'confirmed')
            ->whereIn('payment_status', ['pending', 'expired'])
            ->count();

        // 3. COD orders awaiting cash (shipped or delivered, payment pending)
        $codAwaitingCashCount = Order::where('payment_method', 'cod')
            ->whereIn('order_status', ['shipped', 'delivered'])
            ->where('payment_status', 'pending')
            ->count();

        // 4. Shipments in transit (shipped, out_for_delivery)
        $shipmentsInTransitCount = Shipment::whereIn('status', ['shipped', 'out_for_delivery'])->count();

        // 5. Cancelled orders needing a refund record
        $cancelledNeedingRefundCount = Order::where('order_status', 'cancelled')
            ->where('payment_status', 'paid')
            ->count();

        // 6. Low stock variants
        $lowStockCount = ProductVariant::whereNull('deleted_at')
            ->lowStock($lowStockThreshold)
            ->count();

        // 7. Out of stock variants
        $outOfStockCount = ProductVariant::whereNull('deleted_at')
            ->outOfStock()
            ->count();

        return [
            [
                'title' => 'Orders Awaiting Processing',
                'description' => 'Confirmed orders ready for warehouse pick & pack',
                'count' => $awaitingProcessingCount,
                'urgent' => $awaitingProcessingCount > 0,
                'link' => route('admin.orders.index', ['queue' => 'awaiting_processing']),
                'badge' => 'Processing',
            ],
            [
                'title' => 'KHQR Awaiting Payment',
                'description' => 'Unpaid or expired Bakong transactions awaiting settlement',
                'count' => $khqrAwaitingPaymentCount,
                'urgent' => false,
                'link' => route('admin.orders.index', ['queue' => 'khqr_awaiting_payment']),
                'badge' => 'KHQR',
            ],
            [
                'title' => 'COD Awaiting Cash Collection',
                'description' => 'Dispatched or delivered COD parcels needing driver cash-in',
                'count' => $codAwaitingCashCount,
                'urgent' => false,
                'link' => route('admin.orders.index', ['queue' => 'cod_awaiting_cash']),
                'badge' => 'COD',
            ],
            [
                'title' => 'Shipments in Transit',
                'description' => 'Packages currently active with J&T Express couriers',
                'count' => $shipmentsInTransitCount,
                'urgent' => false,
                'link' => route('admin.shipments.index', ['status' => 'in_transit']),
                'badge' => 'Logistics',
            ],
            [
                'title' => 'Refunds Due (Cancelled Paid Orders)',
                'description' => 'Orders cancelled after customer payment requiring ledger refund',
                'count' => $cancelledNeedingRefundCount,
                'urgent' => $cancelledNeedingRefundCount > 0,
                'link' => route('admin.orders.index', ['queue' => 'refunds_due']),
                'badge' => 'Finance',
            ],
            [
                'title' => 'Low Stock Apparel Variants',
                'description' => "Stock levels at or below threshold ({$lowStockThreshold} units)",
                'count' => $lowStockCount,
                'urgent' => $lowStockCount > 0,
                'link' => route('admin.inventory.index', ['stock_filter' => 'low_stock']),
                'badge' => 'Inventory',
            ],
            [
                'title' => 'Out of Stock Variants',
                'description' => 'Depleted size variants requiring restock purchase orders',
                'count' => $outOfStockCount,
                'urgent' => $outOfStockCount > 0,
                'link' => route('admin.inventory.index', ['stock_filter' => 'out_of_stock']),
                'badge' => 'Stockout',
            ],
        ];
    }

    /**
     * Get 5 most recent orders.
     */
    public function getRecentOrders(int $limit = 5): Collection
    {
        return Order::latest('id')->limit($limit)->get();
    }

    /**
     * Get the last 7 local days revenue and order trend for the mini chart.
     */
    public function getLast7DaysTrend(): array
    {
        $tz = DateBucketHelper::getTimezone();
        $now = Carbon::now($tz);

        $startDateLocal = $now->copy()->subDays(6)->startOfDay();
        $endDateLocal = $now->copy()->endOfDay();

        $startUtc = DateBucketHelper::toUtc($startDateLocal);
        $endUtc = DateBucketHelper::toUtc($endDateLocal);

        $sqlDateExpr = DateBucketHelper::sqlDateBucket('created_at', 'day');

        $rows = Order::select(
            DB::raw("{$sqlDateExpr} as day_bucket"),
            DB::raw("COUNT(*) as total_orders"),
            DB::raw("SUM(CASE WHEN payment_status = 'paid' AND order_status <> 'cancelled' THEN total_amount ELSE 0 END) as day_revenue")
        )
        ->whereBetween('created_at', [$startUtc, $endUtc])
        ->groupBy('day_bucket')
        ->get()
        ->keyBy('day_bucket');

        $trend = [];
        $maxRevenue = 0.0;

        for ($i = 6; $i >= 0; $i--) {
            $dayCarbon = $now->copy()->subDays($i);
            $dayKey = $dayCarbon->format('Y-m-d');
            $row = $rows->get($dayKey);

            $rev = (float) ($row ? $row->day_revenue : 0);
            $orders = (int) ($row ? $row->total_orders : 0);

            if ($rev > $maxRevenue) {
                $maxRevenue = $rev;
            }

            $trend[] = [
                'date' => $dayKey,
                'day_name' => $dayCarbon->format('D'),
                'short_date' => $dayCarbon->format('M d'),
                'revenue' => DateBucketHelper::formatMoney($rev),
                'revenue_float' => $rev,
                'orders' => $orders,
            ];
        }

        return [
            'days' => $trend,
            'max_revenue' => $maxRevenue > 0 ? $maxRevenue : 100.0,
        ];
    }
}
