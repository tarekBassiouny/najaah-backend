<?php

declare(strict_types=1);

namespace App\Enums;

enum LearningAssetType: string
{
    case Summary = 'summary';
    case Flashcards = 'flashcards';
    case InteractiveActivity = 'interactive_activity';
}
