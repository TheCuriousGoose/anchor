<?php

namespace App\Events;

class LabelDeleted extends BoardBroadcastEvent
{
    public function __construct(string $boardId, public string $labelId)
    {
        parent::__construct($boardId);
    }

    public function broadcastAs(): string
    {
        return 'label.deleted';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            ...parent::broadcastWith(),
            'id' => $this->labelId,
        ];
    }
}
