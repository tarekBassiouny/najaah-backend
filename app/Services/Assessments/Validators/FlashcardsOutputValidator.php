<?php

declare(strict_types=1);

namespace App\Services\Assessments\Validators;

use App\Models\AIContentJob;
use App\Services\Assessments\Validators\Concerns\ValidatesAIOutputFields;

final class FlashcardsOutputValidator implements AIOutputValidatorInterface
{
    use ValidatesAIOutputFields;

    public function validate(AIContentJob $job, array $payload): array
    {
        $errors = [];

        $this->validateTextNode($payload['title'] ?? null, 'title', $job->language, $errors);
        $cards = $this->requireArray($payload['cards'] ?? null, 'cards', $errors);

        foreach ($cards as $index => $card) {
            if (! is_array($card)) {
                $errors[] = 'cards.'.$index.' must be an object.';

                continue;
            }

            $this->validateTextNode($card['front'] ?? null, 'cards.'.$index.'.front', $job->language, $errors);
            $this->validateTextNode($card['back'] ?? null, 'cards.'.$index.'.back', $job->language, $errors);
        }

        return $errors;
    }
}
