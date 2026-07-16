<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base for events that follow a *person* rather than a board — a collaborator can't be
 * subscribed to a board channel they've just been given (or just lost) access to, so
 * sharing changes have to reach them on their own private channel.
 *
 * `ShouldRescue` for the same reason as BoardBroadcastEvent: a websocket outage must not
 * fail the already-committed write that triggered the broadcast.
 */
abstract class UserBroadcastEvent implements ShouldBroadcastNow, ShouldRescue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $userId) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("App.Models.User.{$this->userId}")];
    }
}
