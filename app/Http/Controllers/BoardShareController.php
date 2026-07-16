<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Events\BoardAccessChanged;
use App\Events\BoardAccessGranted;
use App\Events\BoardAccessRevoked;
use App\Events\BoardCollaboratorsChanged;
use App\Http\Requests\ShareBoardRequest;
use App\Http\Requests\UpdateBoardShareRequest;
use App\Models\AuditLog;
use App\Models\Board;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class BoardShareController extends Controller
{
    public function store(ShareBoardRequest $request, Board $board): JsonResponse
    {
        $this->authorize('manageSharing', $board);

        $data = $request->validated();
        $collaborator = User::where('email', $data['email'])->firstOrFail();

        abort_if($collaborator->id === $board->user_id, 422, 'The board owner already has access.');

        $board->collaborators()->syncWithoutDetaching([
            $collaborator->id => ['role' => $data['role']],
        ]);

        // The new collaborator isn't on the board channel yet, so they're told directly.
        broadcast(new BoardAccessGranted($collaborator->id, $board, $data['role']));
        broadcast(new BoardCollaboratorsChanged($board))->toOthers();

        return response()->json($this->collaborator($board, $collaborator->id), 201);
    }

    public function update(UpdateBoardShareRequest $request, Board $board, User $user): JsonResponse
    {
        $this->authorize('manageSharing', $board);

        /** @var string $role */
        $role = $request->validated('role');

        $board->collaborators()->updateExistingPivot($user->id, ['role' => $role]);

        broadcast(new BoardAccessChanged($user->id, $board->id, $role));
        broadcast(new BoardCollaboratorsChanged($board))->toOthers();

        return response()->json($this->collaborator($board, $user->id));
    }

    public function destroy(Board $board, User $user): JsonResponse
    {
        $this->authorize('manageSharing', $board);

        $board->collaborators()->detach($user->id);

        AuditLog::record(AuditAction::BoardShareRevoked, $board, $board->name, [
            'collaborator_email' => $user->email,
        ]);

        broadcast(new BoardAccessRevoked($user->id, $board->id, $board->name));
        broadcast(new BoardCollaboratorsChanged($board))->toOthers();

        return response()->json(status: 204);
    }

    /** @return array<string, mixed> */
    private function collaborator(Board $board, int $userId): array
    {
        $collaborator = $board->collaborators()->findOrFail($userId);

        return [
            'id' => $collaborator->id,
            'name' => $collaborator->name,
            'email' => $collaborator->email,
            'role' => $collaborator->pivot->role,
        ];
    }
}
