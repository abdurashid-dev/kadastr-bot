<?php

use App\Http\Controllers\FilesController;
use App\Http\Controllers\FileApprovalController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard', [
        'user' => request()->user()
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
        Route::get('statistics', [UserController::class, 'statistics'])->name('statistics');
        Route::get('{user}', [UserController::class, 'show'])->name('show');
        Route::put('{user}', [UserController::class, 'update'])->name('update');
        Route::put('{user}/role', [UserController::class, 'updateRole'])->name('update-role');
        Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy');
    });
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
