<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Reports\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    /**
     * Display the Executive Admin Dashboard with real-time live metrics and action queues.
     */
    public function index(Request $request)
    {
        $metrics = $this->dashboardService->getMetrics();
        $actionQueues = $this->dashboardService->getActionQueues();
        $recentOrders = $this->dashboardService->getRecentOrders(5);
        $trend = $this->dashboardService->getLast7DaysTrend();

        return view('admin.dashboard', [
            'metrics' => $metrics,
            'actionQueues' => $actionQueues,
            'recentOrders' => $recentOrders,
            'trend' => $trend,
        ]);
    }
}
