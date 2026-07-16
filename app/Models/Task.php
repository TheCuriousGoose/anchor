<?php

namespace App\Models;

use App\Enums\TaskPriority;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $id
 * @property string $board_id
 * @property string $title
 * @property string|null $description
 * @property bool $completed
 * @property int $position
 * @property TaskPriority|null $priority
 * @property Carbon|null $due_date
 * @property-read Board $board
 * @property-read Collection<int, Label> $labels
 */
#[Fillable(['title', 'description', 'completed', 'position', 'priority', 'due_date'])]
class Task extends Model implements HasMedia
{
    use HasUuids, InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('content-images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
    }

    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
            'priority' => TaskPriority::class,
            'due_date' => 'datetime',
        ];
    }

    /** @return BelongsTo<Board, $this> */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /** @return BelongsToMany<Label, $this> */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class);
    }
}
