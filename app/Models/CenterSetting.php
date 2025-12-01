<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $center_id
 * @property array<mixed> $settings
 * @property-read Center $center
 */
class CenterSetting extends Model
{
    /** @use HasFactory<\Database\Factories\CenterSettingFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'center_id',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    /** @return BelongsTo<Center, self> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
