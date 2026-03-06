<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EducationalStage;
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
 * @property int $center_id
 * @property array<string, string> $name_translations
 * @property string $slug
 * @property EducationalStage $stage
 * @property int $order
 * @property bool $is_active
 * @property-read Center $center
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $students
 */
class Grade extends Model
{
    /** @use HasFactory<\Database\Factories\GradeFactory> */
    use HasFactory;

    use HasTranslatableSearch;
    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'center_id',
        'name_translations',
        'slug',
        'stage',
        'order',
        'is_active',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'stage' => EducationalStage::class,
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    /** @var array<int, string> */
    protected array $translatable = ['name'];

    /** @return BelongsTo<Center, self> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /** @return HasMany<User, self> */
    public function students(): HasMany
    {
        return $this->hasMany(User::class, 'grade_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForCenter(Builder $query, int $centerId): Builder
    {
        return $query->where('center_id', $centerId);
    }
}
