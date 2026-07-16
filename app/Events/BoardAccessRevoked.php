<?php

namespace App\Events;

class BoardAccessRevoked extends UserBroadcastEvent
{
    public function __construct(int $userId, public string $boardId, public string $boardName)
    {
        parent::__construct($userId);
    }

    public function broadcastAs(): string
    {
        return 'board.access.revoked';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'boardId' => $this->boardId,
            'boardName' => $this->boardName,
        ];
    }
}
