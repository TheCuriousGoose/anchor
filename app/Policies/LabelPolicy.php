<?php

namespace App\Policies;

use App\Models\Label;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class LabelPolicy
{
    public function update(User $user, Label $label): Response
    {
        return Gate::forUser($user)->check('update', $label->board)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function delete(User $user, Label $label): Response
    {
        return $this->update($user, $label);
    }
}
