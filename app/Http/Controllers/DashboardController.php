<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
}
