<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportFilterRequest;
use App\Services\AdminAuditLogger;
use App\Services\Reports\ReportService;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * Sales & Revenue Report (daily/monthly breakdown, payment split, status counts).
     */
    public function sales(ReportFilterRequest $request)
    {
        [$from, $to] = $request->dateRange();

        if ($request->input('format') === 'csv') {
            $this->logExportAudit('sales', $from, $to);
            return $this->reportService->exportSalesCsv($from, $to);
        }

        $data = $this->reportService->getSalesReport($from, $to);

        return view('admin.reports.sales', [
            'report' => $data,
            'dateFrom' => $from->toDateString(),
            'dateTo' => $to->toDateString(),
        ]);
    }

    /**
     * Products & Sizing Performance Report.
     */
    public function products(ReportFilterRequest $request)
    {
        [$from, $to] = $request->dateRange();

        if ($request->input('format') === 'csv') {
            $this->logExportAudit('products', $from, $to);
            return $this->reportService->exportProductsCsv($from, $to);
        }

        $data = $this->reportService->getProductsReport($from, $to);

        return view('admin.reports.products', [
            'report' => $data,
            'dateFrom' => $from->toDateString(),
            'dateTo' => $to->toDateString(),
        ]);
    }

    /**
     * Regional Delivery & Revenue Breakdown Report.
     */
    public function regions(ReportFilterRequest $request)
    {
        [$from, $to] = $request->dateRange();

        if ($request->input('format') === 'csv') {
            $this->logExportAudit('regions', $from, $to);
            return $this->reportService->exportRegionsCsv($from, $to);
        }

        $data = $this->reportService->getRegionsReport($from, $to);

        return view('admin.reports.regions', [
            'report' => $data,
            'dateFrom' => $from->toDateString(),
            'dateTo' => $to->toDateString(),
        ]);
    }

    /**
     * Customer Loyalty & Acquisition Report.
     */
    public function customers(ReportFilterRequest $request)
    {
        [$from, $to] = $request->dateRange();

        $data = $this->reportService->getCustomersReport($from, $to);

        return view('admin.reports.customers', [
            'report' => $data,
            'dateFrom' => $from->toDateString(),
            'dateTo' => $to->toDateString(),
        ]);
    }

    /**
     * Record an audit log for administrative export actions.
     */
    protected function logExportAudit(string $reportType, Carbon $from, Carbon $to): void
    {
        AdminAuditLogger::log(
            module: 'reports',
            action: 'export',
            targetTable: 'reports',
            targetId: null,
            oldValues: null,
            newValues: [
                'report' => $reportType,
                'filters' => [
                    'date_from' => $from->toDateString(),
                    'date_to' => $to->toDateString(),
                ],
            ]
        );
    }
}
