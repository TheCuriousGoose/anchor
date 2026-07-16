<?php

namespace App\Events;

class NoteDeleted extends BoardBroadcastEvent
{
    public function __construct(string $boardId, public string $noteId)
    {
        parent::__construct($boardId);
    }

    public function broadcastAs(): string
    {
        return 'note.deleted';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            ...parent::broadcastWith(),
            'id' => $this->noteId,
        ];
    }
}
