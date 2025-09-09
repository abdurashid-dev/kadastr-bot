<?php

use App\Http\Controllers\FileApprovalController;
use App\Http\Controllers\FilesController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Language switching route
Route::post('/language', [LanguageController::class, 'changeLanguage'])->name('language.change');

Route::get('dashboard', function () {
    $user = request()->user();

    // Get file statistics
    $fileStats = [
        'total_files' => \App\Models\UploadedFile::count(),
        'files_by_status' => \App\Models\UploadedFile::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status'),
        'files_by_region' => \App\Models\UploadedFile::join('users', 'uploaded_files.user_id', '=', 'users.id')
            ->selectRaw('users.region, count(*) as count, sum(registered_count) as registered_count')
            ->groupBy('users.region')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->region => [
                    'count' => $item->count,
                    'registered_count' => $item->registered_count ?? 0,
                ]];
            }),
        'files_by_type' => \App\Models\UploadedFile::selectRaw('file_type, count(*) as count')
            ->groupBy('file_type')
            ->pluck('count', 'file_type'),
        'recent_files' => \App\Models\UploadedFile::with('user')
            ->latest()
            ->limit(5)
            ->get(['id', 'name', 'status', 'file_type', 'created_at', 'user_id']),
        'monthly_stats' => [
            'daily' => \App\Models\UploadedFile::selectRaw(
                \DB::getDriverName() === 'sqlite'
                    ? 'strftime("%Y-%m-%d", created_at) as date, count(*) as count'
                    : (\DB::getDriverName() === 'pgsql'
                        ? 'to_char(created_at, \'YYYY-MM-DD\') as date, count(*) as count'
                        : 'DATE_FORMAT(created_at, "%Y-%m-%d") as date, count(*) as count')
            )
                ->where('created_at', '>=', now()->subMonths(6))
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date'),
            'hourly' => \App\Models\UploadedFile::selectRaw(
                \DB::getDriverName() === 'sqlite'
                    ? 'strftime("%Y-%m-%d %H:00:00", created_at) as hour, count(*) as count'
                    : (\DB::getDriverName() === 'pgsql'
                        ? 'to_char(created_at, \'YYYY-MM-DD HH24:00:00\') as hour, count(*) as count'
                        : 'DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour, count(*) as count')
            )
                ->where('created_at', '>=', now()->subDays(1))
                ->groupBy('hour')
                ->orderBy('hour')
                ->pluck('count', 'hour'),
            'monthly' => \App\Models\UploadedFile::selectRaw(
                \DB::getDriverName() === 'sqlite'
                    ? 'strftime("%Y-%m", created_at) as month, count(*) as count'
                    : (\DB::getDriverName() === 'pgsql'
                        ? 'to_char(created_at, \'YYYY-MM\') as month, count(*) as count'
                        : 'DATE_FORMAT(created_at, "%Y-%m") as month, count(*) as count')
            )
                ->where('created_at', '>=', now()->subMonths(12))
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('count', 'month'),
        ],
    ];

    return Inertia::render('Dashboard', [
        'user' => $user,
        'fileStats' => $fileStats,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('files', [FilesController::class, 'index'])->name('files.index');
    Route::get('files/{file}', [FilesController::class, 'show'])->name('files.show');
    Route::get('files/{file}/download', [FilesController::class, 'download'])->name('files.download');
    Route::patch('files/{file}/status', [FilesController::class, 'updateStatus'])->name('files.update-status');
    Route::post('files/{file}/status', [FilesController::class, 'updateStatus'])->name('files.update-status-post');
    Route::delete('files/{file}', [FilesController::class, 'destroy'])->name('files.destroy');

    // File Approval Workflow Routes
    Route::prefix('approval')->name('approval.')->group(function () {
        // User routes
        Route::get('history', [FileApprovalController::class, 'userHistory'])->name('history');

        // Checker routes
        Route::get('pending', [FileApprovalController::class, 'pendingFiles'])->name('pending');
        Route::post('files/{file}/approve-checker', [FileApprovalController::class, 'approveByChecker'])->name('approve-checker');

        // Registrator routes
        Route::get('waiting', [FileApprovalController::class, 'waitingFiles'])->name('waiting');
        Route::post('files/{file}/approve-registrator', [FileApprovalController::class, 'approveByRegistrator'])->name('approve-registrator');

        // Common rejection route
        Route::post('files/{file}/reject', [FileApprovalController::class, 'reject'])->name('reject');

        // CEO analytics route
        Route::get('analytics', [FileApprovalController::class, 'analytics'])->name('analytics');
    });

    // User Management Routes
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('{user}', [UserController::class, 'update'])->name('update');
        Route::get('statistics', [UserController::class, 'statistics'])->name('statistics');
        Route::get('{user}', [UserController::class, 'show'])->name('show');
        Route::put('{user}/role', [UserController::class, 'updateRole'])->name('update-role');
        Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
