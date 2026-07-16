<?php

namespace App\Models;

use App\Enums\AuditAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * An append-only record of consequential actions. Written explicitly from controllers
 * (this codebase has no observer layer, and the broadcast events are inert under
 * BROADCAST_CONNECTION=null, so neither is a safe place to hang audit writes).
 *
 * @property int $id
 * @property int|null $actor_id
 * @property string $actor_label
 * @property AuditAction $action
 * @property string|null $target_type
 * @property string|null $target_id
 * @property string|null $target_label
 * @property array<string, mixed>|null $metadata
 * @property string|null $ip_address
 * @property Carbon|null $created_at
 * @property-read User|null $actor
 */
#[Fillable(['actor_id', 'actor_label', 'action', 'target_type', 'target_id', 'target_label', 'metadata', 'ip_address'])]
class AuditLog extends Model
{
    /** Audit rows are immutable once written, so there is nothing for updated_at to mean. */
    public const UPDATED_AT = null;

    /**
     * Record an action by the currently authenticated user.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function record(
        AuditAction $action,
        ?Model $target = null,
        ?string $targetLabel = null,
        array $metadata = [],
    ): self {
        // Null when this runs from the console — see the admin:promote command.
        $actor = Auth::user();

        return self::create([
            'actor_id' => $actor?->id,
            'actor_label' => $actor === null ? 'system' : $actor->email,
            'action' => $action,
            'target_type' => $target === null ? null : class_basename($target),
            'target_id' => $target === null ? null : (string) $target->getKey(),
            'target_label' => $targetLabel,
            'metadata' => $metadata === [] ? null : $metadata,
            'ip_address' => Request::ip(),
        ]);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
