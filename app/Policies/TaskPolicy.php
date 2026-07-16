<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class TaskPolicy
{
    public function update(User $user, Task $task): Response
    {
        return Gate::forUser($user)->check('update', $task->board)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function delete(User $user, Task $task): Response
    {
        return $this->update($user, $task);
    }
}
