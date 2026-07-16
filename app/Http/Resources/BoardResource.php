<?php

namespace App\Http\Resources;

use App\Models\Board;
use App\Models\BoardInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Board */
class BoardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();
        $isOwner = $this->user_id === $user->id;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'icon' => $this->icon,
            'tasks' => $this->whenLoaded('tasks', fn () => $this->tasks, []),
            'notes' => $this->whenLoaded('notes', fn () => $this->notes, []),
            'labels' => $this->whenLoaded('labels', fn () => $this->labels, []),
            'isOwner' => $isOwner,
            'role' => $isOwner ? 'owner' : $this->collaborators->firstWhere('id', $user->id)?->pivot->role,
            'collaborators' => $isOwner
                ? $this->collaborators->map(fn (User $collaborator): array => [
                    'id' => $collaborator->id,
                    'name' => $collaborator->name,
                    'email' => $collaborator->email,
                    'role' => $collaborator->pivot->role,
                    'pending' => false,
                ])->values()
                : [],
            // Only the owner manages sharing, so only the owner is told who is still pending.
            'invitations' => $isOwner
                ? $this->whenLoaded(
                    'invitations',
                    fn () => $this->invitations
                        ->filter(fn (BoardInvitation $invitation) => ! $invitation->isExpired())
                        ->map(fn (BoardInvitation $invitation): array => [
                            'id' => $invitation->id,
                            'name' => null,
                            'email' => $invitation->email,
                            'role' => $invitation->role,
                            'pending' => true,
                        ])->values(),
                    [],
                )
                : [],
        ];
    }
}
