<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $center_id
 * @property int $uploaded_by
 * @property string $bunny_upload_id
 * @property int|null $library_id
 * @property int $upload_status
 * @property int $progress_percent
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property-read Center $center
 * @property-read User $uploader
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Video> $videos
 */
class VideoUploadSession extends Model
{
    /** @use HasFactory<\Database\Factories\VideoUploadSessionFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'center_id',
        'uploaded_by',
        'library_id',
        'bunny_upload_id',
        'upload_status',
        'progress_percent',
        'error_message',
        'expires_at',
    ];

    protected $casts = [
        'upload_status' => 'integer',
        'progress_percent' => 'integer',
        'library_id' => 'integer',
        'expires_at' => 'datetime',
    ];

    /** @return BelongsTo<Center, self> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /** @return BelongsTo<User, self> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** @return HasMany<Video, self> */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class, 'upload_session_id');
    }
}
