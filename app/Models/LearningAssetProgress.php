<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LearningAssetProgressStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $center_id
 * @property int $course_id
 * @property int $learning_asset_id
 * @property int $user_id
 * @property int|null $enrollment_id
 * @property LearningAssetProgressStatus $status
 * @property int $progress_percent
 * @property array<string, mixed>|null $state
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $last_interacted_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Center $center
 * @property-read Course $course
 * @property-read LearningAsset $learningAsset
 * @property-read User $user
 * @property-read Enrollment|null $enrollment
 */
class LearningAssetProgress extends Model
{
    /** @use HasFactory<\Database\Factories\LearningAssetProgressFactory> */
    use HasFactory;

    protected $table = 'learning_asset_progress';

    /** @var list<string> */
    protected $fillable = [
        'center_id',
        'course_id',
        'learning_asset_id',
        'user_id',
        'enrollment_id',
        'status',
        'progress_percent',
        'state',
        'started_at',
        'last_interacted_at',
        'completed_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => LearningAssetProgressStatus::class,
        'progress_percent' => 'integer',
        'state' => 'array',
        'started_at' => 'datetime',
        'last_interacted_at' => 'datetime',
        'completed_at' => 'datetime',
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

    /** @return BelongsTo<LearningAsset, self> */
    public function learningAsset(): BelongsTo
    {
        return $this->belongsTo(LearningAsset::class);
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Enrollment, self> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
