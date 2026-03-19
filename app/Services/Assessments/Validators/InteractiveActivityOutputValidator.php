<?php

declare(strict_types=1);

namespace App\Services\Assessments\Validators;

use App\Models\AIContentJob;
use App\Services\Assessments\Validators\Concerns\ValidatesAIOutputFields;

final class InteractiveActivityOutputValidator implements AIOutputValidatorInterface
{
    use ValidatesAIOutputFields;

    public function validate(AIContentJob $job, array $payload): array
    {
        $errors = [];

        $this->validateTextNode($payload['title'] ?? null, 'title', $job->language, $errors);
        $this->validateTextNode($payload['instructions'] ?? null, 'instructions', $job->language, $errors);

        $steps = $this->requireArray($payload['steps'] ?? null, 'steps', $errors);
        foreach ($steps as $index => $step) {
            if (! is_array($step)) {
                $errors[] = 'steps.'.$index.' must be an object.';

                continue;
            }

            $this->validateTextNode($step['title'] ?? null, 'steps.'.$index.'.title', $job->language, $errors);
            $this->validateTextNode($step['description'] ?? null, 'steps.'.$index.'.description', $job->language, $errors);
            $this->validatePositiveNumber($step['estimated_seconds'] ?? null, 'steps.'.$index.'.estimated_seconds', $errors);
        }

        return $errors;
    }
}
