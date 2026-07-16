<?php

namespace App\Policies;

use App\Enums\BoardRole;
use App\Models\Board;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BoardPolicy
{
    public function view(User $user, Board $board): Response
    {
        return $user->id === $board->user_id || $this->roleFor($user, $board) !== null
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function update(User $user, Board $board): Response
    {
        return $user->id === $board->user_id || $this->roleFor($user, $board) === BoardRole::Editor->value
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function delete(User $user, Board $board): Response
    {
        return $user->id === $board->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function manageSharing(User $user, Board $board): Response
    {
        return $this->delete($user, $board);
    }

    /**
     * Read the pivot role, never a bare `role` column. `users` also has a `role`
     * (see UserRole), so selecting an unqualified `role` off this join is ambiguous,
     * and selecting `board_user.role` would hydrate it onto User::$role and trip the
     * UserRole cast. Only the aliased `pivot_role` is safe on both counts.
     */
    private function roleFor(User $user, Board $board): ?string
    {
        return $board->relationLoaded('collaborators')
            ? $board->collaborators->firstWhere('id', $user->id)?->pivot->role
            : $board->collaborators()->where('users.id', $user->id)->first()?->pivot->role;
    }
}
