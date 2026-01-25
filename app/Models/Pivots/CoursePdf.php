<?php

declare(strict_types=1);

namespace App\Models\Pivots;

use App\Models\Course;
use App\Models\Pdf;
use App\Models\Section;
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
}
