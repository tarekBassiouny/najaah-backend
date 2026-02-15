<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SurveyAssignableType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $survey_id
 * @property SurveyAssignableType $assignable_type
 * @property int $assignable_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Survey $survey
 * @property-read Model $assignable
 */
class SurveyAssignment extends Model
{
    /** @use HasFactory<\Database\Factories\SurveyAssignmentFactory> */
    use HasFactory;

    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'survey_id',
        'assignable_type',
        'assignable_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'assignable_type' => SurveyAssignableType::class,
        'assignable_id' => 'integer',
    ];

    /** @return BelongsTo<Survey, self> */
    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    /** @return MorphTo<Model, self> */
    public function assignable(): MorphTo
    {
        return $this->morphTo('assignable', 'assignable_type', 'assignable_id')
            ->withoutGlobalScopes();
    }

    /**
     * Get the actual model for the assignable.
     */
    public function getAssignableModelAttribute(): ?Model
    {
        $modelClass = $this->assignable_type->modelClass();

        if ($modelClass === null) {
            return null;
        }

        return $modelClass::find($this->assignable_id);
    }

    /**
     * Scope to filter assignments by assignable type and ID.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForAssignable(Builder $query, SurveyAssignableType $type, int $id): Builder
    {
        return $query->where('assignable_type', $type)
            ->where('assignable_id', $id);
    }
}
