<?php

namespace App\Http\Controllers;

use App\Models\UploadedFile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

class FileApprovalController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display pending files for checkers, registrators, and CEOs
     */
    public function pendingFiles(Request $request): Response
    {
        $this->authorize('viewAny', UploadedFile::class);

        if (!in_array($request->user()->role, ['checker', 'registrator', 'ceo'])) {
            abort(403);
        }

        $files = UploadedFile::with('user')
            ->pending()
            ->latest()
            ->paginate(20);

        return Inertia::render('FileApproval/PendingFiles', [
            'files' => $files,
            'user' => $request->user(),
        ]);
    }

    /**
     * Display waiting files for registrators and CEOs
     */
    public function waitingFiles(Request $request): Response
    {
        $this->authorize('viewAny', UploadedFile::class);

        if (!in_array($request->user()->role, ['registrator', 'ceo'])) {
            abort(403);
        }

        $files = UploadedFile::with('user')
            ->waiting()
            ->latest()
            ->paginate(20);

        return Inertia::render('FileApproval/WaitingFiles', [
            'files' => $files,
            'user' => $request->user(),
        ]);
    }

    /**
     * Display user's upload history
     */
    public function userHistory(Request $request): Response
    {
        $files = $request->user()
            ->uploadedFiles()
            ->latest()
            ->paginate(20);

        return Inertia::render('FileApproval/UserHistory', [
            'files' => $files,
        ]);
    }

    /**
     * Display analytics dashboard for CEOs
     */
    public function analytics(Request $request): Response
    {
        $this->authorize('viewAnalytics', UploadedFile::class);

        if (!$request->user()->isCeo()) {
            abort(403);
        }

        $stats = [
            'accepted' => UploadedFile::accepted()->count(),
            'rejected' => UploadedFile::rejected()->count(),
            'pending' => UploadedFile::pending()->count(),
            'waiting' => UploadedFile::waiting()->count(),
            'total' => UploadedFile::count(),
        ];

        return Inertia::render('FileApproval/Analytics', [
            'stats' => $stats,
        ]);
    }

    /**
     * Approve file by checker
     */
    public function approveByChecker(Request $request, UploadedFile $file): JsonResponse
    {
        $this->authorize('approveAsChecker', $file);

        if (!$request->user()->isChecker()) {
            abort(403);
        }

        if ($file->approveByChecker()) {
            return response()->json([
                'message' => 'File approved successfully',
                'status' => $file->status,
            ]);
        }

        return response()->json([
            'message' => 'Unable to approve file',
        ], 400);
    }

    /**
     * Approve file by registrator
     */
    public function approveByRegistrator(Request $request, UploadedFile $file): JsonResponse
    {
        $this->authorize('approveAsRegistrator', $file);

        if (!$request->user()->isRegistrator()) {
            abort(403);
        }

        if ($file->approveByRegistrator()) {
            return response()->json([
                'message' => 'File approved successfully',
                'status' => $file->status,
            ]);
        }

        return response()->json([
            'message' => 'Unable to approve file',
        ], 400);
    }

    /**
     * Reject file
     */
    public function reject(Request $request, UploadedFile $file): JsonResponse
    {
        $this->authorize('reject', $file);

        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($file->reject($request->notes)) {
            return response()->json([
                'message' => 'File rejected successfully',
                'status' => $file->status,
            ]);
        }

        return response()->json([
            'message' => 'Unable to reject file',
        ], 400);
    }
}
