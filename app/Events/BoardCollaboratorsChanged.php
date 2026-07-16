<?php

namespace App\Events;

use App\Models\Board;
use App\Models\User;

/**
 * The collaborator roster changed. Only the owner's UI renders the roster, but this goes
 * to the whole board channel so any owner tab (and the presence stack) stays current.
 */
class BoardCollaboratorsChanged extends BoardBroadcastEvent
{
    public function __construct(public Board $board)
    {
        parent::__construct($board->id);
    }

    public function broadcastAs(): string
    {
        return 'board.collaborators.changed';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        // `load` rather than `loadMissing`: this fires right after an attach/detach/role
        // change, so any relation already on the model is stale by definition.
        return [
            ...parent::broadcastWith(),
            'collaborators' => $this->board->load('collaborators')->collaborators
                ->map(fn (User $collaborator): array => [
                    'id' => $collaborator->id,
                    'name' => $collaborator->name,
                    'email' => $collaborator->email,
                    'role' => $collaborator->pivot->role,
                ])->values()->all(),
        ];
    }
}
