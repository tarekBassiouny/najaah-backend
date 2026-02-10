<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SurveyScopeType;
use App\Enums\SurveyType;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property SurveyScopeType $scope_type
 * @property int|null $center_id
 * @property array<string, string> $title_translations
 * @property array<string, string>|null $description_translations
 * @property SurveyType $type
 * @property bool $is_active
 * @property bool $is_mandatory
 * @property bool $allow_multiple_submissions
 * @property \Carbon\Carbon|null $start_at
 * @property \Carbon\Carbon|null $end_at
 * @property int $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Center|null $center
 * @property-read User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SurveyQuestion> $questions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SurveyAssignment> $assignments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SurveyResponse> $responses
 */
class Survey extends Model
{
    /** @use HasFactory<\Database\Factories\SurveyFactory> */
    use HasFactory;

    use HasTranslations;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'scope_type',
        'center_id',
        'title_translations',
        'description_translations',
        'type',
        'is_active',
        'is_mandatory',
        'allow_multiple_submissions',
        'start_at',
        'end_at',
        'created_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'scope_type' => SurveyScopeType::class,
        'type' => SurveyType::class,
        'title_translations' => 'array',
        'description_translations' => 'array',
        'is_active' => 'boolean',
        'is_mandatory' => 'boolean',
        'allow_multiple_submissions' => 'boolean',
        'start_at' => 'date',
        'end_at' => 'date',
    ];

    /** @var array<int, string> */
    protected array $translatable = [
        'title',
        'description',
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

    /** @return HasMany<SurveyQuestion, self> */
    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('order_index');
    }

    /** @return HasMany<SurveyAssignment, self> */
    public function assignments(): HasMany
    {
        return $this->hasMany(SurveyAssignment::class);
    }

    /** @return HasMany<SurveyResponse, self> */
    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function isSystem(): bool
    {
        return $this->scope_type === SurveyScopeType::System;
    }

    public function isCenter(): bool
    {
        return $this->scope_type === SurveyScopeType::Center;
    }

    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = now()->toDateString();

        if ($this->start_at !== null && $today < $this->start_at->toDateString()) {
            return false;
        }

        if ($this->end_at !== null && $today > $this->end_at->toDateString()) {
            return false;
        }

        return true;
    }

    /**
     * Scope to filter active surveys.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter surveys by scope type.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForScope(Builder $query, SurveyScopeType $scopeType): Builder
    {
        return $query->where('scope_type', $scopeType);
    }

    /**
     * Scope to filter surveys for a specific center.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForCenter(Builder $query, int $centerId): Builder
    {
        return $query->where('center_id', $centerId);
    }

    /**
     * Scope to filter system-wide surveys.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('scope_type', SurveyScopeType::System);
    }

    /**
     * Scope to filter available surveys (active and within date range).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where('is_active', true)
            ->where(function (Builder $q) use ($today): void {
                $q->whereNull('start_at')
                    ->orWhereDate('start_at', '<=', $today);
            })
            ->where(function (Builder $q) use ($today): void {
                $q->whereNull('end_at')
                    ->orWhereDate('end_at', '>=', $today);
            });
    }

    /**
     * Scope to filter surveys for a center (includes center-specific + system surveys).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForCenterWithSystem(Builder $query, int $centerId): Builder
    {
        return $query->where(function (Builder $q) use ($centerId): void {
            $q->where('center_id', $centerId)
                ->orWhereNull('center_id');
        });
    }

    /**
     * Check if survey is scoped to a specific center.
     */
    public function isCenterScoped(): bool
    {
        return $this->center_id !== null;
    }

    /**
     * Check if survey is system-wide (not scoped to a center).
     */
    public function isSystemScoped(): bool
    {
        return $this->center_id === null;
    }
}
