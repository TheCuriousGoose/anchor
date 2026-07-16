<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string $board_id
 * @property int $user_id
 * @property string $role
 */
class BoardUser extends Pivot
{
    //
}
