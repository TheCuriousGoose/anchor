<?php

namespace App\Http\Controllers;

use App\Http\Resources\BoardResource;
use App\Models\Board;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Workspace', [
            'board' => null,
        ]);
    }

    public function dashboard(Request $request): Response|RedirectResponse
    {
        $board = $this->accessibleBoardsQuery($request->user())->latest()->first();

        if ($board) {
            return to_route('boards.show', $board);
        }

        return Inertia::render('Workspace', [
            'board' => null,
        ]);
    }

    public function index(Request $request): Response
    {
        $boards = $this->accessibleBoardsQuery($request->user())
            ->with(['tasks', 'notes', 'collaborators'])
            ->latest()
            ->get();

        return Inertia::render('Boards', [
            'boards' => BoardResource::collection($boards)->resolve(),
        ]);
    }

    public function show(Request $request, Board $board): Response
    {
        $this->authorize('view', $board);

        $board->load(['tasks', 'notes', 'collaborators']);

        return Inertia::render('Workspace', [
            'board' => (new BoardResource($board))->resolve(),
        ]);
    }

    /** @return Builder<Board> */
    private function accessibleBoardsQuery(User $user)
    {
        return Board::query()->accessibleBy($user);
    }
}
