<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->withCount('uploadedFiles')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%");
                });
            })
            ->when($request->role, function ($query, $role) {
                $query->where('role', $role);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role']),
            'roles' => ['user', 'checker', 'registrator', 'ceo'],
        ]);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): Response
    {
        $this->authorize('view', $user);

        $user->loadCount('uploadedFiles');
        $user->load(['uploadedFiles' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return Inertia::render('Users/Show', [
            'user' => $user,
        ]);
    }

    /**
     * Update the specified user's role.
     */
    public function updateRole(Request $request, User $user): JsonResponse
    {
        $this->authorize('assignRole', $user);

        $request->validate([
            'role' => 'required|in:user,checker,registrator,ceo',
        ]);

        // Prevent changing role of the last CEO
        if ($user->isCeo() && User::where('role', 'ceo')->count() <= 1) {
            return response()->json([
                'message' => 'Cannot change role of the last CEO',
            ], 422);
        }

        $user->update(['role' => $request->role]);

        return response()->json([
            'message' => 'User role updated successfully',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Update the specified user's profile.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:20',
            'region' => 'nullable|string|max:255',
        ]);

        $user->update($request->only(['name', 'email', 'phone_number', 'region']));

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        // Prevent deletion of the last admin user
        if ($user->isCeo() && User::where('role', 'ceo')->count() <= 1) {
            return response()->json([
                'message' => 'Cannot delete the last CEO user',
            ], 422);
        }

        // Prevent users from deleting themselves
        if (auth()->id() === $user->id) {
            return response()->json([
                'message' => 'You cannot delete your own account',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Get user statistics for dashboard.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $stats = [
            'total_users' => User::count(),
            'users_by_role' => User::selectRaw('role, count(*) as count')
                ->groupBy('role')
                ->pluck('count', 'role'),
            'recent_users' => User::latest()->limit(5)->get(['id', 'name', 'email', 'role', 'created_at']),
        ];

        return response()->json($stats);
    }
}
