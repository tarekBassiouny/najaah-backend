<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string $entity_type
 * @property int $entity_id
 * @property array<mixed> $metadata
 * @property-read User|null $user
 * @property-read Model|null $entity
 */
class AuditLog extends Model
{
    use HasFactory, SoftDeletes;

    protected array $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'metadata',
    ];

    protected array $casts = [
        'metadata' => 'array',
    ];

    /** @return BelongsTo<User, AuditLog> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphTo<Model, AuditLog> */
    public function entity(): MorphTo
    {
        return $this->morphTo(
            name: 'entity',
            type: 'entity_type',
            id: 'entity_id'
        );
    }
}
