<?php

namespace App\Events;

use App\Models\Label;

class LabelCreated extends BoardBroadcastEvent
{
    public function __construct(public Label $label)
    {
        parent::__construct($label->board_id);
    }

    public function broadcastAs(): string
    {
        return 'label.created';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            ...parent::broadcastWith(),
            'label' => $this->label->toArray(),
        ];
    }
}
