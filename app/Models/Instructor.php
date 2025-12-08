<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Models\Pivots\CourseInstructor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $center_id
 * @property array<string, string> $name_translations
 * @property array<string, string>|null $bio_translations
 * @property array<string, string>|null $title_translations
 * @property string|null $avatar_url
 * @property string|null $email
 * @property string|null $phone
 * @property array<string, mixed>|null $social_links
 * @property int $created_by
 * @property-read Center|null $center
 * @property-read User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Course> $courses
 */
class Instructor extends Model
{
    /** @use HasFactory<\Database\Factories\InstructorFactory> */
    use HasFactory;

    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'center_id',
        'name_translations',
        'bio_translations',
        'title_translations',
        'avatar_url',
        'email',
        'phone',
        'social_links',
        'created_by',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'bio_translations' => 'array',
        'title_translations' => 'array',
        'social_links' => 'array',
    ];

    /** @var array<int, string> */
    protected array $translatable = [
        'name',
        'bio',
        'title',
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

    /** @return BelongsToMany<Course, self> */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_instructors')
            ->using(CourseInstructor::class)
            ->withPivot(['role', 'created_at', 'updated_at', 'deleted_at'])
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }
}
