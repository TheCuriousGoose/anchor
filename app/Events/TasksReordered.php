<?php

namespace App\Events;

class TasksReordered extends BoardBroadcastEvent
{
    /** @param  array<int, string>  $taskIds  Task ids in their new order, position === index. */
    public function __construct(string $boardId, public array $taskIds)
    {
        parent::__construct($boardId);
    }

    public function broadcastAs(): string
    {
        return 'tasks.reordered';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            ...parent::broadcastWith(),
            'taskIds' => $this->taskIds,
        ];
    }
}
