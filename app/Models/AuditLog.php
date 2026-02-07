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
 * @property int|null $center_id
 * @property int|null $course_id
 * @property string $action
 * @property string $entity_type
 * @property int $entity_id
 * @property array<mixed> $metadata
 * @property-read User|null $user
 * @property-read Model|null $entity
 */
class AuditLog extends Model
{
    /** @use HasFactory<\Database\Factories\AuditLogFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'center_id',
        'course_id',
        'action',
        'entity_type',
        'entity_id',
        'metadata',
    ];

    protected $casts = [
        'center_id' => 'integer',
        'course_id' => 'integer',
        'metadata' => 'array',
    ];

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return MorphTo<Model, self> */
    public function entity(): MorphTo
    {
        return $this->morphTo(
            name: 'entity',
            type: 'entity_type',
            id: 'entity_id'
        );
    }
}
