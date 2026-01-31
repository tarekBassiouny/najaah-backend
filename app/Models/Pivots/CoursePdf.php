<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use App\Models\Course;
use App\Models\Pdf;
use App\Models\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoursePdf extends Pivot
{
    use SoftDeletes;

    protected $table = 'course_pdf';

    public $incrementing = true;

    public $timestamps = true;

    protected $fillable = [
        'course_id',
        'pdf_id',
        'section_id',
        'video_id',
        'order_index',
        'visible',
    ];

    protected $casts = [
        'order_index' => 'integer',
        'visible' => 'boolean',
    ];

    /** @return BelongsTo<Course, self> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<Pdf, self> */
    public function pdf(): BelongsTo
    {
        return $this->belongsTo(Pdf::class);
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
    public function scopeForPdf(Builder $query, Pdf $pdf): Builder
    {
        return $query->where('pdf_id', $pdf->id);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForPdfId(Builder $query, int $pdfId): Builder
    {
        return $query->where('pdf_id', $pdfId);
    }
}
