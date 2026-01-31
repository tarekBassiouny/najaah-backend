<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use App\Models\Course;
use App\Models\Section;
use App\Models\Video;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseVideo extends Pivot
{
    use SoftDeletes;

    protected $table = 'course_video';

    public $incrementing = true;

    public $timestamps = true;

    protected $fillable = [
        'course_id',
        'video_id',
        'section_id',
        'order_index',
        'visible',
        'view_limit_override',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'visible' => 'boolean',
        'view_limit_override' => 'integer',
    ];

    /** @return BelongsTo<Course, self> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<Video, self> */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /** @return BelongsTo<Section, self> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
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
    public function scopeForCourseId(Builder $query, int $courseId): Builder
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForSection(Builder $query, Section $section): Builder
    {
        return $query->where('section_id', $section->id);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForSectionId(Builder $query, int $sectionId): Builder
    {
        return $query->where('section_id', $sectionId);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForVideo(Builder $query, Video $video): Builder
    {
        return $query->where('video_id', $video->id);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForVideoId(Builder $query, int $videoId): Builder
    {
        return $query->where('video_id', $videoId);
    }
}
