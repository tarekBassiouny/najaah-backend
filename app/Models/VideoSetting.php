<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $video_id
 * @property array<mixed> $settings
 * @property-read Video $video
 */
class VideoSetting extends Model
{
    /** @use HasFactory<\Database\Factories\VideoSettingFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'video_id',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    /** @return BelongsTo<Video, self> */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}
