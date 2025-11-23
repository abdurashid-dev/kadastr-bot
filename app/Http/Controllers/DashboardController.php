<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $sortBy = $request->get('sort_by', 'accepted_objects');
        $sortOrder = $request->get('sort_order', 'desc');

        $fileStats = $this->dashboardService->getFileStatistics([
            'status_period' => $statusPeriod,
            'region_period' => $regionPeriod,
            'files_region_period' => $filesRegionPeriod,
            'trend_period' => $trendPeriod,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
        ], $startDate, $endDate);

        // Get bot information for Telegram connection
        $bot = TelegraphBot::first();
        $botUsername = null;
        if ($bot && $bot->token) {
            try {
                $response = Http::timeout(5)->get("https://api.telegram.org/bot{$bot->token}/getMe");
                if ($response->successful()) {
                    $botInfo = $response->json();
                    $botUsername = $botInfo['result']['username'] ?? null;
                }
            } catch (\Exception $e) {
                // Bot info not available
            }
        }

        return Inertia::render('Dashboard', [
            'user' => $user,
            'fileStats' => $fileStats,
            'statusData' => $fileStats['files_by_status'],
            'regionData' => $fileStats['files_by_region'],
            'filesRegionData' => $fileStats['files_by_region_files_only'],
            'trendData' => $fileStats['monthly_stats'],
            'regionStatistics' => $fileStats['region_statistics'],
            'filters' => $request->only(['start_date', 'end_date', 'sort_by', 'sort_order']),
            'botUsername' => $botUsername,
            'hasTelegramId' => ! empty($user->telegram_id),
        ]);
    }

    public function connectTelegram(Request $request)
    {
        $bot = TelegraphBot::first();

        if (! $bot || ! $bot->token) {
            return response()->json([
                'success' => false,
                'message' => 'Telegram bot is not configured.',
            ], 400);
        }

        try {
            $response = Http::timeout(5)->get("https://api.telegram.org/bot{$bot->token}/getMe");
            if ($response->successful()) {
                $botInfo = $response->json();
                $botUsername = $botInfo['result']['username'] ?? null;

                if ($botUsername) {
                    // Generate a secure token for the user
                    $user = $request->user();
                    $token = $user->generateTelegramConnectionToken();

                    // Generate a deep link to start the bot with the token
                    $deepLink = "https://t.me/{$botUsername}?start=connect_{$token}";

                    return response()->json([
                        'success' => true,
                        'deepLink' => $deepLink,
                        'botUsername' => $botUsername,
                        'expiresIn' => 10, // minutes
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Handle error
        }

        return response()->json([
            'success' => false,
            'message' => 'Unable to connect to Telegram bot.',
        ], 500);
    }

    public function exportRegionStatistics(Request $request): StreamedResponse
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $format = $request->get('format', 'csv'); // csv or excel

        $fileStats = $this->dashboardService->getFileStatistics([
            'status_period' => 'month',
            'region_period' => 'month',
            'files_region_period' => 'month',
            'trend_period' => 'month',
            'sort_by' => 'accepted_objects',
            'sort_order' => 'desc',
        ], $startDate, $endDate);

        $statistics = $fileStats['region_statistics'];

        // Format dates for display (dd/mm/yyyy)
        $periodStart = $startDate ? date('d/m/Y', strtotime($startDate)) : '';
        $periodEnd = $endDate ? date('d/m/Y', strtotime($endDate)) : '';
        $periodText = $periodStart && $periodEnd ? "{$periodStart} - {$periodEnd}" : '';

        if ($format === 'excel') {
            return $this->exportToExcel($statistics, $periodText, $startDate, $endDate);
        }

        return $this->exportToCSV($statistics, $periodText);
    }

    private function exportToCSV(array $statistics, string $periodText): StreamedResponse
    {
        $filename = 'region_statistics_' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($statistics, $periodText) {
            $handle = fopen('php://output', 'w');

            // Add BOM for UTF-8 to ensure Excel displays correctly
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Write period information
            if ($periodText) {
                fputcsv($handle, ['Davr:', $periodText], ';');
                fputcsv($handle, [], ';'); // Empty row
            }

            // Write headers
            fputcsv($handle, ['Tr', 'Tumanlar', 'Qabul qilingan objectlar', 'Qabul qilingan fayllar', 'Rad etilgan fayllar'], ';');

            // Write data
            foreach ($statistics as $index => $stat) {
                fputcsv($handle, [
                    $index + 1,
                    $stat['region'],
                    $stat['accepted_objects'] ?? 0,
                    $stat['accepted_files'] ?? 0,
                    $stat['rejected_files'] ?? 0,
                ], ';');
            }

            // Calculate and write totals
            $totals = [
                'accepted_objects' => array_sum(array_column($statistics, 'accepted_objects')),
                'accepted_files' => array_sum(array_column($statistics, 'accepted_files')),
                'rejected_files' => array_sum(array_column($statistics, 'rejected_files')),
            ];

            fputcsv($handle, [], ';'); // Empty row
            fputcsv($handle, ['', 'Jami', $totals['accepted_objects'], $totals['accepted_files'], $totals['rejected_files']], ';');

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function exportToExcel(array $statistics, string $periodText, ?string $startDate, ?string $endDate): StreamedResponse
    {
        $filename = 'region_statistics_' . date('Y-m-d') . '.xls';

        return response()->streamDownload(function () use ($statistics, $periodText) {
            $handle = fopen('php://output', 'w');

            // Add BOM for UTF-8 to ensure Excel displays correctly
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Write period information
            if ($periodText) {
                fputcsv($handle, ['Davr:', $periodText], "\t");
                fputcsv($handle, [], "\t"); // Empty row
            }

            // Write headers
            fputcsv($handle, ['Tr', 'Tumanlar', 'Qabul qilingan objectlar', 'Qabul qilingan fayllar', 'Rad etilgan fayllar'], "\t");

            // Write data
            foreach ($statistics as $index => $stat) {
                fputcsv($handle, [
                    $index + 1,
                    $stat['region'],
                    $stat['accepted_objects'] ?? 0,
                    $stat['accepted_files'] ?? 0,
                    $stat['rejected_files'] ?? 0,
                ], "\t");
            }

            // Calculate and write totals
            $totals = [
                'accepted_objects' => array_sum(array_column($statistics, 'accepted_objects')),
                'accepted_files' => array_sum(array_column($statistics, 'accepted_files')),
                'rejected_files' => array_sum(array_column($statistics, 'rejected_files')),
            ];

            fputcsv($handle, [], "\t"); // Empty row
            fputcsv($handle, ['', 'Jami', $totals['accepted_objects'], $totals['accepted_files'], $totals['rejected_files']], "\t");

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
