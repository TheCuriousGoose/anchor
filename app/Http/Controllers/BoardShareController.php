<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShareBoardRequest;
use App\Http\Requests\UpdateBoardShareRequest;
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

        return response()->json($this->collaborator($board, $collaborator->id), 201);
    }

    public function update(UpdateBoardShareRequest $request, Board $board, User $user): JsonResponse
    {
        $this->authorize('manageSharing', $board);

        $board->collaborators()->updateExistingPivot($user->id, ['role' => $request->validated('role')]);

        return response()->json($this->collaborator($board, $user->id));
    }

    public function destroy(Board $board, User $user): JsonResponse
    {
        $this->authorize('manageSharing', $board);

        $board->collaborators()->detach($user->id);

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
