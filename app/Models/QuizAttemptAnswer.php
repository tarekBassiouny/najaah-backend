<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $quiz_attempt_id
 * @property int $quiz_question_id
 * @property array<int>|null $selected_answer_ids
 * @property bool $is_correct
 * @property float $points_earned
 * @property \Carbon\Carbon|null $answered_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read QuizAttempt $attempt
 * @property-read QuizQuestion $question
 */
class QuizAttemptAnswer extends Model
{
    /** @use HasFactory<\Database\Factories\QuizAttemptAnswerFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'quiz_attempt_id',
        'quiz_question_id',
        'selected_answer_ids',
        'is_correct',
        'points_earned',
        'answered_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'selected_answer_ids' => 'array',
        'is_correct' => 'boolean',
        'points_earned' => 'decimal:2',
        'answered_at' => 'datetime',
    ];

    /** @return BelongsTo<QuizAttempt, self> */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    /** @return BelongsTo<QuizQuestion, self> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }

    public function hasAnswer(): bool
    {
        return $this->selected_answer_ids !== null && count($this->selected_answer_ids) > 0;
    }
}
