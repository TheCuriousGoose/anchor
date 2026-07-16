<?php

namespace App\Events;

use App\Models\Task;

class TaskCreated extends BoardBroadcastEvent
{
    public function __construct(public Task $task)
    {
        parent::__construct($task->board_id);
    }

    public function broadcastAs(): string
    {
        return 'task.created';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            ...parent::broadcastWith(),
            'task' => $this->task->loadMissing('labels')->toArray(),
        ];
    }
}
