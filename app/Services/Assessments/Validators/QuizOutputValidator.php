<?php

declare(strict_types=1);

namespace App\Services\Assessments\Validators;

use App\Models\AIContentJob;
use App\Services\Assessments\Validators\Concerns\ValidatesAIOutputFields;

final class QuizOutputValidator implements AIOutputValidatorInterface
{
    use ValidatesAIOutputFields;

    public function validate(AIContentJob $job, array $payload): array
    {
        $errors = [];
        $quiz = is_array($payload['quiz'] ?? null) ? $payload['quiz'] : [];

        $this->validateTextNode($quiz['title'] ?? null, 'quiz.title', $job->language, $errors);
        $this->validateTextNode($quiz['description'] ?? null, 'quiz.description', $job->language, $errors);

        $questions = $this->requireArray($payload['questions'] ?? null, 'questions', $errors);
        foreach ($questions as $questionIndex => $question) {
            if (! is_array($question)) {
                $errors[] = 'questions.'.$questionIndex.' must be an object.';

                continue;
            }

            $this->validateTextNode($question['question'] ?? null, 'questions.'.$questionIndex.'.question', $job->language, $errors);

            if (array_key_exists('explanation', $question)) {
                $this->validateTextNode($question['explanation'], 'questions.'.$questionIndex.'.explanation', $job->language, $errors);
            }

            $this->validatePositiveNumber($question['points'] ?? null, 'questions.'.$questionIndex.'.points', $errors);

            $options = $this->requireArray($question['options'] ?? null, 'questions.'.$questionIndex.'.options', $errors);
            if (count($options) < 2) {
                $errors[] = 'questions.'.$questionIndex.'.options must contain at least 2 options.';
            }

            $correctCount = 0;
            foreach ($options as $optionIndex => $option) {
                if (is_string($option)) {
                    if (trim($option) === '') {
                        $errors[] = 'questions.'.$questionIndex.'.options.'.$optionIndex.' must not be empty.';
                    }

                    continue;
                }

                if (! is_array($option)) {
                    $errors[] = 'questions.'.$questionIndex.'.options.'.$optionIndex.' must be a string or object.';

                    continue;
                }

                $this->validateTextNode(
                    $option['text'] ?? null,
                    'questions.'.$questionIndex.'.options.'.$optionIndex.'.text',
                    $job->language,
                    $errors
                );

                if (! array_key_exists('is_correct', $option) || ! is_bool($option['is_correct'])) {
                    $errors[] = 'questions.'.$questionIndex.'.options.'.$optionIndex.'.is_correct must be boolean.';
                } elseif ($option['is_correct'] === true) {
                    $correctCount++;
                }
            }

            if ($correctCount < 1) {
                $errors[] = 'questions.'.$questionIndex.' must include at least one correct option.';
            }
        }

        return $errors;
    }
}
