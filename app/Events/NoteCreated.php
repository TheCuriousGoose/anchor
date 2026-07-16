<?php

namespace App\Events;

use App\Models\Note;

class NoteCreated extends BoardBroadcastEvent
{
    public function __construct(public Note $note)
    {
        parent::__construct($note->board_id);
    }

    public function broadcastAs(): string
    {
        return 'note.created';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            ...parent::broadcastWith(),
            'note' => $this->note->toArray(),
        ];
    }
}
