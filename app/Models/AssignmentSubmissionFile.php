<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $assignment_submission_id
 * @property string $file_name
 * @property string $file_path
 * @property int $file_size_kb
 * @property string $file_type
 * @property string $storage_provider
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read AssignmentSubmission $submission
 */
class AssignmentSubmissionFile extends Model
{
    /** @use HasFactory<\Database\Factories\AssignmentSubmissionFileFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'assignment_submission_id',
        'file_name',
        'file_path',
        'file_size_kb',
        'file_type',
        'storage_provider',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'file_size_kb' => 'integer',
    ];

    /** @return BelongsTo<AssignmentSubmission, self> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssignmentSubmission::class, 'assignment_submission_id');
    }

    public function getFileSizeMb(): float
    {
        return round($this->file_size_kb / 1024, 2);
    }

    public function getFileExtension(): string
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }
}
