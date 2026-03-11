<?php

declare(strict_types=1);

namespace App\Enums;

enum AIContentTargetType: string
{
    case Quiz = 'quiz';
    case Assignment = 'assignment';
    case Summary = 'summary';
    case Flashcards = 'flashcards';
    case InteractiveActivity = 'interactive_activity';
}
