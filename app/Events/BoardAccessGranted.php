<?php

namespace App\Events;

use App\Models\Board;

class BoardAccessGranted extends UserBroadcastEvent
{
    public function __construct(int $userId, public Board $board, public string $role)
    {
        parent::__construct($userId);
    }

    public function broadcastAs(): string
    {
        return 'board.access.granted';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'board' => [
                'id' => $this->board->id,
                'name' => $this->board->name,
                'icon' => $this->board->icon,
            ],
            'role' => $this->role,
            'sharedBy' => $this->board->user->name,
        ];
    }
}
