<?php

namespace App\Services\Reports;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    /**
     * Generate Sales & Revenue Report metrics.
     */
    public function getSalesReport(Carbon $from, Carbon $to): array
    {
        $utcFrom = DateBucketHelper::toUtc($from);
        $utcTo = DateBucketHelper::toUtc($to);

        $daysCount = $from->diffInDays($to);
        $interval = $daysCount > 92 ? 'month' : 'day';

        $sqlDateExpr = DateBucketHelper::sqlDateBucket('created_at', $interval);

        // 1. Time-bucket breakdown
        $bucketRows = Order::select(
            DB::raw("{$sqlDateExpr} as period"),
            DB::raw("COUNT(CASE WHEN payment_status = 'paid' AND order_status <> 'cancelled' THEN 1 END) as paid_orders"),
            DB::raw("SUM(CASE WHEN payment_status = 'paid' AND order_status <> 'cancelled' THEN total_amount ELSE 0 END) as revenue")
        )
        ->whereBetween('created_at', [$utcFrom, $utcTo])
        ->groupBy('period')
        ->orderBy('period', 'asc')
        ->get();

        $buckets = [];
        $totalRevenue = 0.0;
        $totalPaidOrders = 0;

        foreach ($bucketRows as $row) {
            $rev = (float) $row->revenue;
            $orders = (int) $row->paid_orders;
            $aov = $orders > 0 ? ($rev / $orders) : 0.0;

            $totalRevenue += $rev;
            $totalPaidOrders += $orders;

            $buckets[] = [
                'period' => $row->period,
                'paid_orders' => $orders,
                'revenue' => DateBucketHelper::formatMoney($rev),
                'revenue_float' => $rev,
                'aov' => DateBucketHelper::formatMoney($aov),
            ];
        }

        $overallAov = $totalPaidOrders > 0 ? ($totalRevenue / $totalPaidOrders) : 0.0;

        // 2. Payment Method Split
        $paymentSplit = Order::select(
            'payment_method',
            DB::raw("COUNT(*) as total_orders"),
            DB::raw("COUNT(CASE WHEN payment_status = 'paid' AND order_status <> 'cancelled' THEN 1 END) as paid_orders"),
            DB::raw("SUM(CASE WHEN payment_status = 'paid' AND order_status <> 'cancelled' THEN total_amount ELSE 0 END) as revenue")
        )
        ->whereBetween('created_at', [$utcFrom, $utcTo])
        ->where('order_status', '<>', 'cancelled')
        ->groupBy('payment_method')
        ->get()
        ->map(function ($row) use ($totalRevenue) {
            $rev = (float) $row->revenue;
            $share = $totalRevenue > 0 ? round(($rev / $totalRevenue) * 100, 1) : 0.0;

            return [
                'method' => $row->payment_method,
                'label' => $row->payment_method === 'aba_khqr' ? 'ABA KHQR' : strtoupper($row->payment_method),
                'total_orders' => (int) $row->total_orders,
                'paid_orders' => (int) $row->paid_orders,
                'revenue' => DateBucketHelper::formatMoney($rev),
                'share' => $share,
            ];
        });

        // 3. Order Status Counts
        $statusCounts = Order::select(
            'order_status',
            DB::raw("COUNT(*) as count")
        )
        ->whereBetween('created_at', [$utcFrom, $utcTo])
        ->groupBy('order_status')
        ->pluck('count', 'order_status')
        ->toArray();

        // 4. Pending Revenue (non-cancelled orders with pending or processing payment)
        $pendingRevenue = (string) (Order::where('order_status', '<>', 'cancelled')
            ->whereIn('payment_status', ['pending', 'processing'])
            ->whereBetween('created_at', [$utcFrom, $utcTo])
            ->sum('total_amount') ?? '0.00');

        return [
            'interval' => $interval,
            'buckets' => $buckets,
            'total_revenue' => DateBucketHelper::formatMoney($totalRevenue),
            'total_paid_orders' => $totalPaidOrders,
            'overall_aov' => DateBucketHelper::formatMoney($overallAov),
            'payment_split' => $paymentSplit,
            'status_counts' => $statusCounts,
            'pending_revenue' => DateBucketHelper::formatMoney($pendingRevenue),
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * Generate Top Products & Size Breakdown Report.
     */
    public function getProductsReport(Carbon $from, Carbon $to): array
    {
        $utcFrom = DateBucketHelper::toUtc($from);
        $utcTo = DateBucketHelper::toUtc($to);

        // Top 20 Products by Units Sold (grouping by order_items.product_name snapshot)
        $topProducts = OrderItem::select(
            'order_items.product_name',
            DB::raw("SUM(order_items.quantity) as units_sold"),
            DB::raw("SUM(order_items.subtotal) as total_revenue")
        )
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->where('orders.payment_status', 'paid')
        ->where('orders.order_status', '<>', 'cancelled')
        ->whereBetween('orders.created_at', [$utcFrom, $utcTo])
        ->groupBy('order_items.product_name')
        ->orderByDesc('units_sold')
        ->limit(20)
        ->get()
        ->map(function ($row) {
            $units = (int) $row->units_sold;
            $rev = (float) $row->total_revenue;
            $avgPrice = $units > 0 ? ($rev / $units) : 0.0;

            return [
                'product_name' => $row->product_name,
                'units_sold' => $units,
                'total_revenue' => DateBucketHelper::formatMoney($rev),
                'avg_price' => DateBucketHelper::formatMoney($avgPrice),
            ];
        });

        // Size breakdown
        $sizeBreakdown = OrderItem::select(
            'order_items.variant_size',
            DB::raw("SUM(order_items.quantity) as units_sold"),
            DB::raw("SUM(order_items.subtotal) as total_revenue")
        )
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->where('orders.payment_status', 'paid')
        ->where('orders.order_status', '<>', 'cancelled')
        ->whereBetween('orders.created_at', [$utcFrom, $utcTo])
        ->groupBy('order_items.variant_size')
        ->orderByDesc('units_sold')
        ->get()
        ->map(function ($row) {
            return [
                'size' => $row->variant_size,
                'units_sold' => (int) $row->units_sold,
                'total_revenue' => DateBucketHelper::formatMoney($row->total_revenue),
            ];
        });

        return [
            'top_products' => $topProducts,
            'size_breakdown' => $sizeBreakdown,
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * Generate Regional Geographic Breakdown Report.
     */
    public function getRegionsReport(Carbon $from, Carbon $to): array
    {
        $utcFrom = DateBucketHelper::toUtc($from);
        $utcTo = DateBucketHelper::toUtc($to);

        $totalRevQuery = Order::where('payment_status', 'paid')
            ->where('order_status', '<>', 'cancelled')
            ->whereBetween('created_at', [$utcFrom, $utcTo])
            ->sum('total_amount');

        $overallRevenue = (float) ($totalRevQuery ?? 0.0);

        $regionStats = Order::select(
            'province',
            DB::raw("COUNT(*) as paid_orders"),
            DB::raw("SUM(total_amount) as total_revenue")
        )
        ->where('payment_status', 'paid')
        ->where('order_status', '<>', 'cancelled')
        ->whereBetween('created_at', [$utcFrom, $utcTo])
        ->groupBy('province')
        ->orderByDesc('total_revenue')
        ->get()
        ->map(function ($row) use ($overallRevenue) {
            $rev = (float) $row->total_revenue;
            $share = $overallRevenue > 0 ? round(($rev / $overallRevenue) * 100, 1) : 0.0;

            return [
                'province' => $row->province,
                'paid_orders' => (int) $row->paid_orders,
                'total_revenue' => DateBucketHelper::formatMoney($rev),
                'share_percentage' => $share,
            ];
        });

        return [
            'regions' => $regionStats,
            'overall_revenue' => DateBucketHelper::formatMoney($overallRevenue),
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * Generate Customer Loyalty & Acquisition Report.
     */
    public function getCustomersReport(Carbon $from, Carbon $to): array
    {
        $utcFrom = DateBucketHelper::toUtc($from);
        $utcTo = DateBucketHelper::toUtc($to);

        // 1. New registered customers in range
        $newRegisteredCustomers = User::where('role', 'customer')
            ->whereBetween('created_at', [$utcFrom, $utcTo])
            ->count();

        // 2. Repeat customer purchase rate
        // Subquery counting all-time paid orders per registered user
        $customerOrderCounts = Order::select('user_id', DB::raw("COUNT(*) as paid_count"))
            ->whereNotNull('user_id')
            ->where('payment_status', 'paid')
            ->where('order_status', '<>', 'cancelled')
            ->groupBy('user_id');

        $customerStats = DB::query()->fromSub($customerOrderCounts, 'user_counts')
            ->select(
                DB::raw("COUNT(CASE WHEN paid_count = 1 THEN 1 END) as one_order_customers"),
                DB::raw("COUNT(CASE WHEN paid_count >= 2 THEN 1 END) as repeat_customers"),
                DB::raw("COUNT(*) as total_paid_customers")
            )->first();

        $oneOrderCount = (int) ($customerStats->one_order_customers ?? 0);
        $repeatCount = (int) ($customerStats->repeat_customers ?? 0);
        $totalPaidCount = (int) ($customerStats->total_paid_customers ?? 0);
        $repeatRate = $totalPaidCount > 0 ? round(($repeatCount / $totalPaidCount) * 100, 1) : 0.0;

        // 3. Guest vs Registered share of orders in range
        $orderShares = Order::select(
            DB::raw("COUNT(CASE WHEN user_id IS NULL THEN 1 END) as guest_orders"),
            DB::raw("COUNT(CASE WHEN user_id IS NOT NULL THEN 1 END) as registered_orders"),
            DB::raw("COUNT(*) as total_orders")
        )
        ->whereBetween('created_at', [$utcFrom, $utcTo])
        ->first();

        $guestOrders = (int) ($orderShares->guest_orders ?? 0);
        $registeredOrders = (int) ($orderShares->registered_orders ?? 0);
        $totalOrders = (int) ($orderShares->total_orders ?? 0);

        $guestSharePct = $totalOrders > 0 ? round(($guestOrders / $totalOrders) * 100, 1) : 0.0;
        $registeredSharePct = $totalOrders > 0 ? round(($registeredOrders / $totalOrders) * 100, 1) : 0.0;

        return [
            'new_customers' => $newRegisteredCustomers,
            'one_order_customers' => $oneOrderCount,
            'repeat_customers' => $repeatCount,
            'total_paid_customers' => $totalPaidCount,
            'repeat_rate' => $repeatRate,
            'guest_orders' => $guestOrders,
            'registered_orders' => $registeredOrders,
            'total_orders' => $totalOrders,
            'guest_share_percentage' => $guestSharePct,
            'registered_share_percentage' => $registeredSharePct,
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * Stream CSV export for Sales Report.
     */
    public function exportSalesCsv(Carbon $from, Carbon $to): StreamedResponse
    {
        $data = $this->getSalesReport($from, $to);
        $filename = 'sales_report_' . $from->format('Ymd') . '_' . $to->format('Ymd') . '.csv';

        $headers = [ucfirst($data['interval']), 'Paid Orders', 'Revenue (USD)', 'Average Order Value (USD)'];
        $rows = [];
        foreach ($data['buckets'] as $bucket) {
            $rows[] = [
                $bucket['period'],
                $bucket['paid_orders'],
                $bucket['revenue'],
                $bucket['aov'],
            ];
        }

        return $this->streamCsv($filename, $headers, $rows);
    }

    /**
     * Stream CSV export for Products Report.
     */
    public function exportProductsCsv(Carbon $from, Carbon $to): StreamedResponse
    {
        $data = $this->getProductsReport($from, $to);
        $filename = 'products_report_' . $from->format('Ymd') . '_' . $to->format('Ymd') . '.csv';

        $headers = ['Product Name', 'Units Sold', 'Total Revenue (USD)', 'Average Unit Price (USD)'];
        $rows = [];
        foreach ($data['top_products'] as $item) {
            $rows[] = [
                $item['product_name'],
                $item['units_sold'],
                $item['total_revenue'],
                $item['avg_price'],
            ];
        }

        return $this->streamCsv($filename, $headers, $rows);
    }

    /**
     * Stream CSV export for Regional Report.
     */
    public function exportRegionsCsv(Carbon $from, Carbon $to): StreamedResponse
    {
        $data = $this->getRegionsReport($from, $to);
        $filename = 'regions_report_' . $from->format('Ymd') . '_' . $to->format('Ymd') . '.csv';

        $headers = ['Province / Region', 'Paid Orders', 'Total Revenue (USD)', 'Share of Revenue (%)'];
        $rows = [];
        foreach ($data['regions'] as $reg) {
            $rows[] = [
                $reg['province'],
                $reg['paid_orders'],
                $reg['total_revenue'],
                $reg['share_percentage'] . '%',
            ];
        }

        return $this->streamCsv($filename, $headers, $rows);
    }

    /**
     * Internal streaming utility with formula sanitization.
     */
    protected function streamCsv(string $filename, array $headers, array $rows): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            // Header row sanitized
            fputcsv($handle, array_map([DateBucketHelper::class, 'sanitizeCsvValue'], $headers));

            // Data rows sanitized
            foreach ($rows as $row) {
                fputcsv($handle, array_map([DateBucketHelper::class, 'sanitizeCsvValue'], $row));
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
