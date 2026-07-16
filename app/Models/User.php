<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\NotificationType;
use App\Enums\UserRole;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property UserRole $role
 * @property CarbonImmutable|null $suspended_at
 * @property array<string, bool>|null $notification_preferences
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
#[Appends('avatar')]
class User extends Authenticatable implements HasMedia, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, InteractsWithMedia, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('avatar')
            ->nonQueued()
            ->fit(Fit::Crop, 512, 512)
            ->format('webp')
            ->quality(80);
    }

    public function getAvatarAttribute(): ?string
    {
        $url = $this->getFirstMediaUrl('avatar', 'avatar');

        return $url === '' ? null : $url;
    }

    /** @return HasMany<Board, $this> */
    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }

    /** @return BelongsToMany<Board, $this, BoardUser> */
    public function sharedBoards(): BelongsToMany
    {
        return $this->belongsToMany(Board::class, 'board_user')
            ->using(BoardUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * Notifications are opt-out: anything the user hasn't explicitly turned off is on,
     * so adding a new type doesn't need a backfill to reach existing accounts.
     */
    public function wantsNotification(NotificationType $type): bool
    {
        return (bool) ($this->notification_preferences[$type->value] ?? true);
    }

    /** @return array<string, bool> */
    public function notificationPreferences(): array
    {
        return collect(NotificationType::cases())
            ->mapWithKeys(fn (NotificationType $type) => [$type->value => $this->wantsNotification($type)])
            ->all();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
            'role' => UserRole::class,
            'suspended_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
