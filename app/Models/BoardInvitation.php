<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A board shared with an email address that has no account yet. Redeemed on registration
 * (see App\Actions\Fortify\CreateNewUser), so the inviter doesn't have to share again.
 *
 * @property int $id
 * @property string $board_id
 * @property int $invited_by
 * @property string $email
 * @property string $role
 * @property string $token
 * @property CarbonImmutable $expires_at
 * @property-read Board $board
 * @property-read User $inviter
 */
#[Fillable(['board_id', 'invited_by', 'email', 'role', 'token', 'expires_at'])]
class BoardInvitation extends Model
{
    public const EXPIRES_AFTER_DAYS = 14;

    public static function freshToken(): string
    {
        return Str::random(64);
    }

    /** @return BelongsTo<Board, $this> */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /** @return BelongsTo<User, $this> */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Hand every board waiting on this address to the user who just claimed it. Matched on
     * email rather than token, so signing up with the invited address is enough — following
     * the emailed link is a convenience, not a requirement.
     *
     * @return int the number of boards granted
     */
    public static function redeemFor(User $user): int
    {
        $invitations = self::query()->pending()->where('email', $user->email)->with('board')->get();
        $granted = 0;

        foreach ($invitations as $invitation) {
            // An owner inviting their own future address would otherwise end up a
            // collaborator on their own board.
            if ($invitation->board->user_id !== $user->id) {
                $invitation->board->collaborators()->syncWithoutDetaching([
                    $user->id => ['role' => $invitation->role],
                ]);

                $granted++;
            }

            $invitation->delete();
        }

        return $granted;
    }

    /**
     * @param  Builder<BoardInvitation>  $query
     * @return Builder<BoardInvitation>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
