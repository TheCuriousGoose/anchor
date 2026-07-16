<?php

namespace App\Models;

use App\Enums\TaskPriority;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $board_id
 * @property string $title
 * @property bool $completed
 * @property int $position
 * @property TaskPriority|null $priority
 * @property-read Board $board
 */
#[Fillable(['title', 'completed', 'position', 'priority'])]
class Task extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
            'priority' => TaskPriority::class,
        ];
    }

    /** @return BelongsTo<Board, $this> */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }
}
