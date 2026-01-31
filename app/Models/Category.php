<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CenterType;
use App\Models\Concerns\HasTranslatableSearch;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $center_id
 * @property array<string, string> $title_translations
 * @property array<string, string>|null $description_translations
 * @property int|null $parent_id
 * @property int $order_index
 * @property bool $is_active
 * @property-read Category|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $children
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Course> $courses
 * @property-read Center|null $center
 */
class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    use HasTranslatableSearch;
    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'center_id',
        'title_translations',
        'description_translations',
        'parent_id',
        'order_index',
        'is_active',
    ];

    protected $casts = [
        'title_translations' => 'array',
        'description_translations' => 'array',
        'is_active' => 'boolean',
        'order_index' => 'integer',
        'center_id' => 'integer',
    ];

    /** @var array<int, string> */
    protected array $translatable = [
        'title',
        'description',
    ];

    /** @return BelongsTo<Category, self> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return BelongsTo<Center, self> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /** @return HasMany<Category, self> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** @return HasMany<Course, self> */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Scope to filter categories visible to a student.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisibleToStudent(Builder $query, User $student): Builder
    {
        if (is_numeric($student->center_id)) {
            return $query->where('center_id', (int) $student->center_id);
        }

        return $query->whereNull('center_id')
            ->orWhereHas('center', function ($query): void {
                $query->where('type', CenterType::Unbranded->value);
            });
    }
}
