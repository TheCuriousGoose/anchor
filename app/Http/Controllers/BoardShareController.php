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
use App\Models\BoardInvitation;
use App\Models\User;
use App\Notifications\BoardAccessRevoked as BoardAccessRevokedNotification;
use App\Notifications\BoardInvitationSent;
use App\Notifications\BoardRoleChanged;
use App\Notifications\BoardShared;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;

class BoardShareController extends Controller
{
    public function store(ShareBoardRequest $request, Board $board): JsonResponse
    {
        $this->authorize('manageSharing', $board);

        $data = $request->validated();
        $actor = $request->user();
        $collaborator = User::where('email', $data['email'])->first();

        // No account behind this address yet — remember the intent and invite them instead.
        if ($collaborator === null) {
            return response()->json($this->invite($board, $data['email'], $data['role']), 201);
        }

        abort_if($collaborator->id === $board->user_id, 422, 'The board owner already has access.');

        $board->collaborators()->syncWithoutDetaching([
            $collaborator->id => ['role' => $data['role']],
        ]);

        $collaborator->notify(new BoardShared($board, $actor, $data['role']));

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

        $user->notify(new BoardRoleChanged($board, $request->user(), $role));

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

        $user->notify(new BoardAccessRevokedNotification($board->name, request()->user()));

        broadcast(new BoardAccessRevoked($user->id, $board->id, $board->name));
        broadcast(new BoardCollaboratorsChanged($board))->toOthers();

        return response()->json(status: 204);
    }

    /** Withdraw an invitation that was never claimed. */
    public function destroyInvitation(Board $board, BoardInvitation $invitation): JsonResponse
    {
        $this->authorize('manageSharing', $board);

        abort_if($invitation->board_id !== $board->id, 404);

        AuditLog::record(AuditAction::BoardShareRevoked, $board, $board->name, [
            'invitation_email' => $invitation->email,
        ]);

        $invitation->delete();

        broadcast(new BoardCollaboratorsChanged($board))->toOthers();

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function invite(Board $board, string $email, string $role): array
    {
        $invitation = BoardInvitation::updateOrCreate(
            ['board_id' => $board->id, 'email' => $email],
            [
                'invited_by' => request()->user()->id,
                'role' => $role,
                'token' => BoardInvitation::freshToken(),
                'expires_at' => now()->addDays(BoardInvitation::EXPIRES_AFTER_DAYS),
            ],
        );

        Notification::route('mail', $email)->notify(new BoardInvitationSent($invitation));

        broadcast(new BoardCollaboratorsChanged($board))->toOthers();

        return [
            'id' => $invitation->id,
            'name' => null,
            'email' => $invitation->email,
            'role' => $invitation->role,
            'pending' => true,
        ];
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
            'pending' => false,
        ];
    }
}
