<?php

namespace App\Events;

/**
 * Sent to everyone sitting on a board that just got deleted, so their tab can bail out
 * to the boards index instead of showing a board that no longer exists.
 */
class BoardDeleted extends BoardBroadcastEvent
{
    public function __construct(string $boardId, public string $boardName)
    {
        parent::__construct($boardId);
    }

    public function broadcastAs(): string
    {
        return 'board.deleted';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            ...parent::broadcastWith(),
            'id' => $this->boardId,
            'name' => $this->boardName,
        ];
    }
}
