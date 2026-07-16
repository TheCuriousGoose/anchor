<?php

namespace App\Events;

use App\Models\Board;

class BoardUpdated extends BoardBroadcastEvent
{
    public function __construct(public Board $board)
    {
        parent::__construct($board->id);
    }

    public function broadcastAs(): string
    {
        return 'board.updated';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            ...parent::broadcastWith(),
            'name' => $this->board->name,
            'icon' => $this->board->icon,
        ];
    }
}
