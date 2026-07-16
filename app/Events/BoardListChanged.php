<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a set of users that their sidebar board list is stale (a board they can see was
 * renamed, deleted, or shared). Carries no payload: the client re-requests the
 * `sidebarBoards` Inertia prop, which keeps a single source of truth for that list.
 *
 * Fans out to one private channel per member, so a rename reaches collaborators who
 * aren't currently looking at the board.
 *
 * `ShouldRescue` for the same reason as BoardBroadcastEvent: a websocket outage must not
 * fail the already-committed write that triggered the broadcast.
 */
class BoardListChanged implements ShouldBroadcastNow, ShouldRescue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @param  array<int, int>  $userIds */
    public function __construct(public array $userIds) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return array_map(
            fn (int $userId): PrivateChannel => new PrivateChannel("App.Models.User.{$userId}"),
            $this->userIds,
        );
    }

    public function broadcastAs(): string
    {
        return 'boards.changed';
    }
}
