<?php

namespace App\Events;

/**
 * A collaborator's role changed (editor <-> viewer). Their open tab needs to re-render
 * with the new permissions, so the client reloads the board rather than patching state.
 */
class BoardAccessChanged extends UserBroadcastEvent
{
    public function __construct(int $userId, public string $boardId, public string $role)
    {
        parent::__construct($userId);
    }

    public function broadcastAs(): string
    {
        return 'board.access.changed';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'boardId' => $this->boardId,
            'role' => $this->role,
        ];
    }
}
