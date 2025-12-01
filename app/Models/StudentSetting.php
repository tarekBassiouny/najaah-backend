<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property array<mixed> $settings
 * @property-read User $user
 */
class StudentSetting extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected array $fillable = [
        'user_id',
        'settings',
    ];

    protected array $casts = [
        'settings' => 'array',
    ];

    /** @return BelongsTo<User, StudentSetting> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
