<?php

use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;

/**
 * Per-user channel used for things that follow the person rather than a board —
 * currently "a board was shared with/unshared from you", which the sidebar listens to.
 */
Broadcast::channel('App.Models.User.{id}', fn (User $user, string $id): bool => $user->id === (int) $id);

/**
 * Presence channel backing real-time collaboration on a single board. Membership is
 * gated by the same `view` policy the board page itself uses, so viewers receive live
 * updates while non-collaborators cannot subscribe at all. The returned array is what
 * every other member sees in the presence roster (the avatar stack).
 */
Broadcast::channel('boards.{board}', function (User $user, Board $board): array|bool {
    if (Gate::forUser($user)->denies('view', $board)) {
        return false;
    }

    $isOwner = $board->user_id === $user->id;

    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar,
        'role' => $isOwner ? 'owner' : $board->collaborators->firstWhere('id', $user->id)?->pivot->role,
    ];
});
