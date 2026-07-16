<?php

namespace App\Events;

class TaskDeleted extends BoardBroadcastEvent
{
    public function __construct(string $boardId, public string $taskId)
    {
        parent::__construct($boardId);
    }

    public function broadcastAs(): string
    {
        return 'task.deleted';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            ...parent::broadcastWith(),
            'id' => $this->taskId,
        ];
    }
}
