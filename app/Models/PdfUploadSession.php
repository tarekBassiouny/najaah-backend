<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PdfUploadStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $center_id
 * @property int $created_by
 * @property string $object_key
 * @property PdfUploadStatus $upload_status
 * @property string|null $error_message
 * @property string $file_extension
 * @property int|null $file_size_kb
 * @property \Illuminate\Support\CarbonImmutable|null $expires_at
 * @property-read Center $center
 * @property-read User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Pdf> $pdfs
 */
class PdfUploadSession extends Model
{
    /** @use HasFactory<\Database\Factories\PdfUploadSessionFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'center_id',
        'created_by',
        'object_key',
        'upload_status',
        'error_message',
        'file_extension',
        'file_size_kb',
        'expires_at',
    ];

    protected $casts = [
        'center_id' => 'integer',
        'created_by' => 'integer',
        'file_size_kb' => 'integer',
        'expires_at' => 'immutable_datetime',
        'upload_status' => PdfUploadStatus::class,
    ];

    /** @return BelongsTo<Center, self> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /** @return BelongsTo<User, self> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<Pdf, self> */
    public function pdfs(): HasMany
    {
        return $this->hasMany(Pdf::class, 'upload_session_id');
    }
}
