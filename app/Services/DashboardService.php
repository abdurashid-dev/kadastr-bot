<?php

namespace App\Services;

use App\Models\UploadedFile;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getFileStatistics(string $period = 'month', ?string $startDate = null, ?string $endDate = null): array
    {
        return [
            'total_files' => UploadedFile::count(),
            'files_by_status' => $this->getFilesByStatus($startDate, $endDate),
            'files_by_region' => $this->getFilesByRegion($startDate, $endDate),
            'files_by_type' => $this->getFilesByType($startDate, $endDate),
            'recent_files' => $this->getRecentFiles(),
            'monthly_stats' => $this->getMonthlyStats($period, $startDate, $endDate),
        ];
    }

    private function getFilesByStatus(?string $startDate = null, ?string $endDate = null): array
    {
        $query = UploadedFile::selectRaw('status, count(*) as count');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                $startDate.' 00:00:00',
                $endDate.' 23:59:59',
            ]);
        }

        return $query->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    private function getFilesByRegion(?string $startDate = null, ?string $endDate = null): array
    {
        $query = UploadedFile::join('users', 'uploaded_files.user_id', '=', 'users.id')
            ->selectRaw('users.region, count(*) as count, sum(registered_count) as registered_count');

        if ($startDate && $endDate) {
            $query->whereBetween('uploaded_files.created_at', [
                $startDate.' 00:00:00',
                $endDate.' 23:59:59',
            ]);
        }

        return $query->groupBy('users.region')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->region => [
                    'count' => $item->count,
                    'registered_count' => $item->registered_count ?? 0,
                ]];
            })
            ->toArray();
    }

    private function getFilesByType(?string $startDate = null, ?string $endDate = null): array
    {
        $query = UploadedFile::selectRaw('file_type, count(*) as count');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                $startDate.' 00:00:00',
                $endDate.' 23:59:59',
            ]);
        }

        return $query->groupBy('file_type')
            ->pluck('count', 'file_type')
            ->toArray();
    }

    private function getRecentFiles()
    {
        return UploadedFile::with('user')
            ->latest()
            ->limit(5)
            ->get(['id', 'name', 'status', 'file_type', 'created_at', 'user_id']);
    }

    private function getMonthlyStats(string $period = 'month', ?string $startDate = null, ?string $endDate = null): array
    {
        return [
            'daily' => $this->getDailyStats($startDate, $endDate),
            'hourly' => $this->getHourlyStats($startDate, $endDate),
            'monthly' => $this->getMonthlyStatsData($startDate, $endDate),
        ];
    }

    private function getDailyStats(?string $startDate = null, ?string $endDate = null): array
    {
        $query = UploadedFile::selectRaw(
            DB::getDriverName() === 'sqlite'
                ? 'strftime("%Y-%m-%d", created_at) as date, count(*) as count'
                : (DB::getDriverName() === 'pgsql'
                    ? 'to_char(created_at, \'YYYY-MM-DD\') as date, count(*) as count'
                    : 'DATE_FORMAT(created_at, "%Y-%m-%d") as date, count(*) as count')
        );

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                $startDate.' 00:00:00',
                $endDate.' 23:59:59',
            ]);
        } else {
            $query->where('created_at', '>=', now()->subMonths(6));
        }

        return $query->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }

    private function getHourlyStats(?string $startDate = null, ?string $endDate = null): array
    {
        $query = UploadedFile::selectRaw(
            DB::getDriverName() === 'sqlite'
                ? 'strftime("%Y-%m-%d %H:00:00", created_at) as hour, count(*) as count'
                : (DB::getDriverName() === 'pgsql'
                    ? 'to_char(created_at, \'YYYY-MM-DD HH24:00:00\') as hour, count(*) as count'
                    : 'DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour, count(*) as count')
        );

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                $startDate.' 00:00:00',
                $endDate.' 23:59:59',
            ]);
        } else {
            $query->where('created_at', '>=', now()->subDays(1));
        }

        return $query->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();
    }

    private function getMonthlyStatsData(?string $startDate = null, ?string $endDate = null): array
    {
        $query = UploadedFile::selectRaw(
            DB::getDriverName() === 'sqlite'
                ? 'strftime("%Y-%m", created_at) as month, count(*) as count'
                : (DB::getDriverName() === 'pgsql'
                    ? 'to_char(created_at, \'YYYY-MM\') as month, count(*) as count'
                    : 'DATE_FORMAT(created_at, "%Y-%m") as month, count(*) as count')
        );

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                $startDate.' 00:00:00',
                $endDate.' 23:59:59',
            ]);
        } else {
            $query->where('created_at', '>=', now()->subMonths(12));
        }

        return $query->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();
    }
}
