<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property string $id
 * @property string $board_id
 * @property string|null $parent_id
 * @property string $title
 * @property string $body
 * @property-read Board $board
 * @property-read Note|null $parent
 * @property-read Collection<int, Note> $children
 */
#[Fillable(['title', 'body', 'parent_id'])]
class Note extends Model implements HasMedia
{
    use HasUuids, InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('content-images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
    }

    /** @return BelongsTo<Board, $this> */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /** @return BelongsTo<Note, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Note::class, 'parent_id');
    }

    /** @return HasMany<Note, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(Note::class, 'parent_id')->oldest();
    }
}
