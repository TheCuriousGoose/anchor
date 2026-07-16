<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property int $user_id
 * @property string $name
 * @property string $icon
 * @property-read Collection<int, User&object{pivot: BoardUser}> $collaborators
 * @property-read int $open_tasks_count
 */
#[Fillable(['name', 'icon'])]
class Board extends Model
{
    use HasUuids;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Task, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('position');
    }

    /** @return HasMany<Note, $this> */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class)->latest();
    }

    /** @return HasMany<Label, $this> */
    public function labels(): HasMany
    {
        return $this->hasMany(Label::class)->orderBy('name');
    }

    /** @return BelongsToMany<User, $this, BoardUser> */
    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'board_user')
            ->using(BoardUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @param  Builder<Board>  $query
     * @return Builder<Board>
     */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id)
            ->orWhereHas('collaborators', fn ($collaborators) => $collaborators->where('users.id', $user->id));
    }
}
