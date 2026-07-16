<?php

namespace App\Http\Resources;

use App\Models\Board;
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
            'isOwner' => $isOwner,
            'role' => $isOwner ? 'owner' : $this->collaborators->firstWhere('id', $user->id)?->pivot->role,
            'collaborators' => $isOwner
                ? $this->collaborators->map(fn (User $collaborator): array => [
                    'id' => $collaborator->id,
                    'name' => $collaborator->name,
                    'email' => $collaborator->email,
                    'role' => $collaborator->pivot->role,
                ])->values()
                : [],
        ];
    }
}
