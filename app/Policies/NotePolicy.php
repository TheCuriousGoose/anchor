<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class NotePolicy
{
    public function update(User $user, Note $note): Response
    {
        return Gate::forUser($user)->check('update', $note->board)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function delete(User $user, Note $note): Response
    {
        return $this->update($user, $note);
    }
}
