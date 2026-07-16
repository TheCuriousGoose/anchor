<?php

namespace App\Http\Middleware;

use App\Models\Board;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'sidebarBoards' => fn () => $this->sidebarBoards($request),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function sidebarBoards(Request $request): array
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return [];
        }

        $boards = Board::query()
            ->accessibleBy($user)
            ->withCount(['tasks as open_tasks_count' => fn ($query) => $query->where('completed', false)])
            ->with(['collaborators' => fn ($query) => $query->where('users.id', $user->id)])
            ->latest()
            ->get();

        $result = [];

        foreach ($boards as $board) {
            $isOwner = $board->user_id === $user->id;

            $result[] = [
                'id' => $board->id,
                'name' => $board->name,
                'icon' => $board->icon,
                'openTasksCount' => $board->open_tasks_count,
                'isOwner' => $isOwner,
                'role' => $isOwner ? 'owner' : $board->collaborators->first()?->pivot->role,
            ];
        }

        return $result;
    }
}
