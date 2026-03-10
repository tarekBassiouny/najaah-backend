<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuestionType;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $quiz_id
 * @property array<string, string> $question_translations
 * @property QuestionType $question_type
 * @property array<string, string>|null $explanation_translations
 * @property float $points
 * @property int $order_index
 * @property bool $is_active
 * @property bool $ai_generated
 * @property string|null $ai_source_type
 * @property int|null $ai_source_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Quiz $quiz
 * @property-read \Illuminate\Database\Eloquent\Collection<int, QuizAnswer> $answers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, QuizAttemptAnswer> $attemptAnswers
 */
class QuizQuestion extends Model
{
    /** @use HasFactory<\Database\Factories\QuizQuestionFactory> */
    use HasFactory;

    use HasTranslations;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'quiz_id',
        'question_translations',
        'question_type',
        'explanation_translations',
        'points',
        'order_index',
        'is_active',
        'ai_generated',
        'ai_source_type',
        'ai_source_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'question_translations' => 'array',
        'question_type' => QuestionType::class,
        'explanation_translations' => 'array',
        'points' => 'decimal:2',
        'order_index' => 'integer',
        'is_active' => 'boolean',
        'ai_generated' => 'boolean',
    ];

    /** @var array<int, string> */
    protected array $translatable = [
        'question',
        'explanation',
    ];

    /** @return BelongsTo<Quiz, self> */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /** @return HasMany<QuizAnswer, self> */
    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class)->orderBy('order_index');
    }

    /** @return HasMany<QuizAttemptAnswer, self> */
    public function attemptAnswers(): HasMany
    {
        return $this->hasMany(QuizAttemptAnswer::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, QuizAnswer>
     */
    public function getCorrectAnswers(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->answers()->where('is_correct', true)->get();
    }

    public function allowsMultipleAnswers(): bool
    {
        return $this->question_type === QuestionType::MultipleChoice;
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
    public function scopeAiGenerated(Builder $query): Builder
    {
        return $query->where('ai_generated', true);
    }
}
