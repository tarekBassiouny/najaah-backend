<?php

declare(strict_types=1);

namespace App\Services\Assessments\Validators;

use App\Models\AIContentJob;
use App\Services\Assessments\Validators\Concerns\ValidatesAIOutputFields;

final class SummaryOutputValidator implements AIOutputValidatorInterface
{
    use ValidatesAIOutputFields;

    public function validate(AIContentJob $job, array $payload): array
    {
        $errors = [];

        $this->validateTextNode($payload['title'] ?? null, 'title', $job->language, $errors);
        $this->validateTextNode($payload['content'] ?? null, 'content', $job->language, $errors);

        return $errors;
    }
}
