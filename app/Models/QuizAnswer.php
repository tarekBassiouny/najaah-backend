<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $quiz_question_id
 * @property array<string, string> $answer_translations
 * @property bool $is_correct
 * @property int $order_index
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read QuizQuestion $question
 */
class QuizAnswer extends Model
{
    /** @use HasFactory<\Database\Factories\QuizAnswerFactory> */
    use HasFactory;

    use HasTranslations;

    /** @var list<string> */
    protected $fillable = [
        'quiz_question_id',
        'answer_translations',
        'is_correct',
        'order_index',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'answer_translations' => 'array',
        'is_correct' => 'boolean',
        'order_index' => 'integer',
    ];

    /** @var array<int, string> */
    protected array $translatable = [
        'answer',
    ];

    /** @return BelongsTo<QuizQuestion, self> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }
}
