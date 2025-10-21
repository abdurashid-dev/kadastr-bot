<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use AuthorizesRequests;

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Users/Create', [
            'roles' => ['user', 'checker', 'registrator', 'ceo'],
            'regions' => $this->getRegions(),
        ]);
    }

    /**
     * Get regions from Telegram registration.
     */
    private function getRegions(): array
    {
        return [
            'Бошқарма',
            'Қувасой шахар',
            'Фарғона шахар',
            'Қўқон шахар',
            'Марғилон шахар',
            'Бешариқ туман',
            'Боғдод туман',
            'Бувайда туман',
            'Данғара туман',
            'Ёзёвон туман',
            'Қува туман',
            'Олтиариқ туман',
            'Қўштепа туман',
            'Риштон туман',
            'Тошлоқ туман',
            'Ўзбекистон туман',
            'Учкўприк туман',
            'Фарғона туман',
            'Фурқат туман',
            'Сўх туман',
        ];
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = User::create($request->validated());

        return redirect()->route('users.index')->with('success', 'User created successfully');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'roles' => ['user', 'checker', 'registrator', 'ceo'],
            'regions' => $this->getRegions(),
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validated();

        // Remove password from update if it's null (not provided)
        if (is_null($validated['password'])) {
            unset($validated['password']);
        }

        // Remove role from update if it's null (not provided)
        if (array_key_exists('role', $validated) && is_null($validated['role'])) {
            unset($validated['role']);
        }

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'User updated successfully');
    }

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
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $this->authorize('assignRole', $user);

        $request->validate([
            'role' => 'required|in:user,checker,registrator,ceo',
        ]);

        // Prevent changing role of the last CEO
        if ($user->isCeo() && User::where('role', 'ceo')->count() <= 1) {
            return redirect()->route('users.index')->with('error', 'Cannot change role of the last CEO');
        }

        $user->update(['role' => $request->role]);

        return redirect()->route('users.index')->with('success', 'User role updated successfully');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        // Prevent deletion of the last admin user
        if ($user->isCeo() && User::where('role', 'ceo')->count() <= 1) {
            return redirect()->route('users.index')->with('error', 'Cannot delete the last CEO user');
        }

        // Prevent users from deleting themselves
        if (auth()->id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully');
    }

    /**
     * Get user statistics for dashboard.
     */
    public function statistics(): Response
    {
        $this->authorize('viewAny', User::class);

        $stats = [
            'total_users' => User::count(),
            'users_by_role' => User::selectRaw('role, count(*) as count')
                ->groupBy('role')
                ->pluck('count', 'role'),
            'recent_users' => User::latest()->limit(5)->get(['id', 'name', 'email', 'role', 'created_at']),
        ];

        return Inertia::render('Users/Statistics', [
            'stats' => $stats,
        ]);
    }

    /**
     * Send a message to user via Telegram.
     */
    public function sendMessage(Request $request, User $user): RedirectResponse
    {
        $this->authorize('view', $user);

        $request->validate([
            'message' => 'required|string|max:4000',
        ]);

        if (! $user->telegram_id) {
            return redirect()->back()->with('error', 'User does not have a Telegram ID.');
        }

        try {
            $bot = TelegraphBot::first();

            if (! $bot || ! $bot->token) {
                return redirect()->back()->with('error', 'Bot configuration not found.');
            }

            $response = Http::timeout(30)->post("https://api.telegram.org/bot{$bot->token}/sendMessage", [
                'chat_id' => $user->telegram_id,
                'text' => "📨 <b>Xabar</b>\n\n".$request->message,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                Log::info('Message sent via Telegram successfully', [
                    'user_id' => $user->id,
                    'telegram_id' => $user->telegram_id,
                    'message_length' => strlen($request->message),
                ]);

                return redirect()->back()->with('success', 'Message sent successfully to '.$user->name.' via Telegram.');
            } else {
                Log::error('Failed to send message via Telegram', [
                    'user_id' => $user->id,
                    'telegram_id' => $user->telegram_id,
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);

                return redirect()->back()->with('error', 'Failed to send message. Please try again.');
            }
        } catch (\Exception $e) {
            Log::error('Error sending message via Telegram', [
                'user_id' => $user->id,
                'telegram_id' => $user->telegram_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'An error occurred while sending the message. Please try again.');
        }
    }
}
