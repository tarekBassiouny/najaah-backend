<?php

declare(strict_types=1);

namespace App\Services\Assessments\Validators;

use App\Models\AIContentJob;
use App\Services\Assessments\Validators\Concerns\ValidatesAIOutputFields;

final class AssignmentOutputValidator implements AIOutputValidatorInterface
{
    use ValidatesAIOutputFields;

    public function validate(AIContentJob $job, array $payload): array
    {
        $errors = [];
        $assignment = is_array($payload['assignment'] ?? null) ? $payload['assignment'] : $payload;

        $this->validateTextNode($assignment['title'] ?? null, 'assignment.title', $job->language, $errors);
        $this->validateTextNode($assignment['description'] ?? null, 'assignment.description', $job->language, $errors);

        $submissionTypes = $this->requireArray($assignment['submission_types'] ?? null, 'assignment.submission_types', $errors);
        foreach ($submissionTypes as $index => $type) {
            if (! is_numeric($type) || ! in_array((int) $type, [0, 1, 2], true)) {
                $errors[] = 'assignment.submission_types.'.$index.' must be one of 0, 1, or 2.';
            }
        }

        $this->validatePositiveNumber($assignment['max_points'] ?? null, 'assignment.max_points', $errors);
        $this->validateNonNegativeNumber($assignment['passing_score'] ?? null, 'assignment.passing_score', $errors);

        if (is_numeric($assignment['max_points'] ?? null) && is_numeric($assignment['passing_score'] ?? null)) {
            if ((float) $assignment['passing_score'] > (float) $assignment['max_points']) {
                $errors[] = 'assignment.passing_score must not exceed assignment.max_points.';
            }
        }

        return $errors;
    }
}
