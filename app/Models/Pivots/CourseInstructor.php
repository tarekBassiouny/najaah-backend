<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use App\Models\Course;
use App\Models\Instructor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @phpstan-use HasFactory<\Database\Factories\Pivots\CourseInstructorFactory>
 *
 * @method static \Database\Factories\Pivots\CourseInstructorFactory factory($count = null, $state = [])
 */
class CourseInstructor extends Pivot
{
    /** @use HasFactory<\Database\Factories\Pivots\CourseInstructorFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'course_instructors';

    public $incrementing = true;

    public $timestamps = true;

    protected $fillable = [
        'course_id',
        'instructor_id',
        'role',
    ];

    protected $casts = [
        'course_id' => 'integer',
        'instructor_id' => 'integer',
    ];

    /** @return BelongsTo<Course, self> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<Instructor, self> */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    /**
     * Scope to exclude soft-deleted pivots.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeNotDeleted(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForCourse(Builder $query, Course $course): Builder
    {
        return $query->where('course_id', $course->id);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForInstructor(Builder $query, Instructor $instructor): Builder
    {
        return $query->where('instructor_id', $instructor->id);
    }
}
