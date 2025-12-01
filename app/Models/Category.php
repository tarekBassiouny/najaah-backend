<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property array<string, string> $title_translations
 * @property array<string, string>|null $description_translations
 * @property int|null $parent_id
 * @property int $order_index
 * @property bool $is_active
 * @property-read Category|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $children
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Course> $courses
 */
class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected array $fillable = [
        'title_translations',
        'description_translations',
        'parent_id',
        'order_index',
        'is_active',
    ];

    protected array $casts = [
        'title_translations' => 'array',
        'description_translations' => 'array',
        'is_active' => 'boolean',
        'order_index' => 'integer',
    ];

    /** @return BelongsTo<Category, Category> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Category, Category> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** @return HasMany<Course, Category> */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
