<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

/**
 * Base for every event that fans out to the people currently viewing a board.
 *
 * These broadcast *now* rather than via the queue: the payloads are small and the whole
 * point is immediacy, so paying a few milliseconds inside the request is a better trade
 * than a collaborator's screen lagging behind a queue worker (or silently never updating
 * because no worker is running).
 *
 * Broadcasting inline means the websocket server sits in the request path, so `ShouldRescue`
 * keeps a Reverb outage from failing the write that triggered it: the change is already
 * committed by the time we broadcast, and reporting a stale sidebar is far better than
 * telling the user their delete failed when it didn't. Failures are still reported.
 */
abstract class BoardBroadcastEvent implements ShouldBroadcastNow, ShouldRescue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ?int $actorId;

    public ?string $actorName;

    public function __construct(public string $boardId)
    {
        $actor = Auth::user();

        $this->actorId = $actor?->id;
        $this->actorName = $actor?->name;
    }

    /** @return array<int, PresenceChannel> */
    public function broadcastOn(): array
    {
        return [new PresenceChannel("boards.{$this->boardId}")];
    }

    /**
     * Subclasses merge their own payload on top of this.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'actor' => $this->actorId === null ? null : [
                'id' => $this->actorId,
                'name' => $this->actorName,
            ],
        ];
    }
}
