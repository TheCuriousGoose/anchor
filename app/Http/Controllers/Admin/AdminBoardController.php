<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Board;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only oversight. This deliberately queries boards directly rather than granting
 * admins a Gate bypass: routes/channels.php authorises the presence channel with the
 * same `view` gate, so a bypass would silently drop admins into collaborators' live
 * avatar rosters. Oversight stays out-of-band and leaves an audit trail instead.
 */
class AdminBoardController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $boards = Board::query()
            ->with('user:id,name,email')
            ->withCount(['tasks', 'notes', 'collaborators'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Board $board) => [
                'id' => $board->id,
                'name' => $board->name,
                'icon' => $board->icon,
                'owner' => [
                    'id' => $board->user->id,
                    'name' => $board->user->name,
                    'email' => $board->user->email,
                ],
                'tasksCount' => $board->tasks_count,
                'notesCount' => $board->notes_count,
                'collaboratorsCount' => $board->collaborators_count,
                'createdAt' => $board->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/Boards', [
            'boards' => $boards,
            'filters' => ['search' => $search],
        ]);
    }

    public function show(Board $board): Response
    {
        $board->load(['user:id,name,email', 'tasks', 'notes', 'collaborators']);

        AuditLog::record(AuditAction::BoardViewedByAdmin, $board, $board->name);

        return Inertia::render('admin/BoardDetail', [
            'board' => [
                'id' => $board->id,
                'name' => $board->name,
                'icon' => $board->icon,
                'owner' => [
                    'id' => $board->user->id,
                    'name' => $board->user->name,
                    'email' => $board->user->email,
                ],
                'createdAt' => $board->created_at?->toIso8601String(),
                'tasks' => $board->tasks->map(fn ($task) => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'completed' => (bool) $task->completed,
                    'priority' => $task->priority,
                ])->values()->all(),
                'notes' => $board->notes->map(fn ($note) => [
                    'id' => $note->id,
                    'title' => $note->title,
                ])->values()->all(),
                'collaborators' => $board->collaborators->map(fn ($collaborator) => [
                    'id' => $collaborator->id,
                    'name' => $collaborator->name,
                    'email' => $collaborator->email,
                    'role' => $collaborator->pivot->role,
                ])->values()->all(),
            ],
        ]);
    }
}
