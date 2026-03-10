<?php

declare(strict_types=1);

namespace App\Enums;

enum QuestionType: int
{
    case SingleChoice = 0;
    case MultipleChoice = 1;

    public function label(): string
    {
        return match ($this) {
            self::SingleChoice => 'Single Choice',
            self::MultipleChoice => 'Multiple Choice',
        };
    }

    public function allowsMultipleAnswers(): bool
    {
        return $this === self::MultipleChoice;
    }
}
