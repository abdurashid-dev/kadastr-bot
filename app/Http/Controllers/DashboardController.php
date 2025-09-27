<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get period parameters for each chart
        $statusPeriod = $request->get('status_period', 'month');
        $regionPeriod = $request->get('region_period', 'month');
        $filesRegionPeriod = $request->get('files_region_period', 'month');
        $trendPeriod = $request->get('trend_period', 'month');

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $fileStats = $this->dashboardService->getFileStatistics([
            'status_period' => $statusPeriod,
            'region_period' => $regionPeriod,
            'files_region_period' => $filesRegionPeriod,
            'trend_period' => $trendPeriod,
        ], $startDate, $endDate);

        return Inertia::render('Dashboard', [
            'user' => $user,
            'fileStats' => $fileStats,
            'statusData' => $fileStats['files_by_status'],
            'regionData' => $fileStats['files_by_region'],
            'filesRegionData' => $fileStats['files_by_region_files_only'],
            'trendData' => $fileStats['monthly_stats'],
        ]);
    }
}
