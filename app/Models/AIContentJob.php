<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AIContentJobStatus;
use App\Enums\AIContentSourceType;
use App\Enums\AIContentTargetType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $center_id
 * @property int $course_id
 * @property string|null $batch_key
 * @property AIContentSourceType $source_type
 * @property int $source_id
 * @property AIContentTargetType $target_type
 * @property int|null $target_id
 * @property string $language
 * @property AIContentJobStatus $status
 * @property array<string,mixed>|null $generation_config
 * @property array<string,mixed>|null $generated_payload
 * @property array<string,mixed>|null $reviewed_payload
 * @property array<int,string>|null $validation_warnings
 * @property string|null $ai_provider
 * @property string|null $ai_model
 * @property int $estimated_input_tokens
 * @property int $estimated_output_tokens
 * @property string|null $prompt_used
 * @property string|null $error_message
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon|null $published_at
 * @property int $created_by
 * @property int|null $reviewed_by
 * @property int|null $published_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Center $center
 * @property-read Course $course
 * @property-read User $creator
 * @property-read User|null $reviewer
 * @property-read User|null $publisher
 */
class AIContentJob extends Model
{
    /** @use HasFactory<\Database\Factories\AIContentJobFactory> */
    use HasFactory;

    protected $table = 'ai_content_jobs';

    /** @var list<string> */
    protected $fillable = [
        'center_id',
        'course_id',
        'batch_key',
        'source_type',
        'source_id',
        'target_type',
        'target_id',
        'language',
        'status',
        'generation_config',
        'generated_payload',
        'reviewed_payload',
        'validation_warnings',
        'ai_provider',
        'ai_model',
        'estimated_input_tokens',
        'estimated_output_tokens',
        'prompt_used',
        'error_message',
        'started_at',
        'completed_at',
        'published_at',
        'created_by',
        'reviewed_by',
        'published_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'source_type' => AIContentSourceType::class,
        'target_type' => AIContentTargetType::class,
        'status' => AIContentJobStatus::class,
        'batch_key' => 'string',
        'language' => 'string',
        'generation_config' => 'array',
        'generated_payload' => 'array',
        'reviewed_payload' => 'array',
        'validation_warnings' => 'array',
        'estimated_input_tokens' => 'integer',
        'estimated_output_tokens' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    /** @return BelongsTo<Center, self> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /** @return BelongsTo<Course, self> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<User, self> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, self> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return BelongsTo<User, self> */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function isPending(): bool
    {
        return $this->status === AIContentJobStatus::Pending;
    }

    public function isProcessing(): bool
    {
        return $this->status === AIContentJobStatus::Processing;
    }

    public function isCompleted(): bool
    {
        return $this->status === AIContentJobStatus::Completed;
    }

    public function isApproved(): bool
    {
        return $this->status === AIContentJobStatus::Approved;
    }

    public function isPublished(): bool
    {
        return $this->status === AIContentJobStatus::Published;
    }

    public function hasGeneratedPayload(): bool
    {
        return is_array($this->generated_payload) && $this->generated_payload !== [];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForCenter(Builder $query, int $centerId): Builder
    {
        return $query->where('center_id', $centerId);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeByStatus(Builder $query, AIContentJobStatus $status): Builder
    {
        return $query->where('status', $status);
    }
}
