<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Quiz;

use App\Models\QuizAnswer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin QuizAnswer
 */
class QuizAnswerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'answer' => $this->translate('answer'),
            'answer_translations' => $this->answer_translations,
            'is_correct' => $this->is_correct,
            'order_index' => $this->order_index,
        ];
    }
}
