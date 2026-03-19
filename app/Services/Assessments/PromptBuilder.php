<?php

declare(strict_types=1);

namespace App\Services\Assessments;

use App\Enums\AIContentTargetType;
use App\Models\AIContentJob;
use Illuminate\Support\Arr;

final class PromptBuilder
{
    /**
     * @return array{system:string,user:string}
     */
    public function build(AIContentJob $job, string $content): array
    {
        return [
            'system' => $this->buildSystemPrompt($job),
            'user' => $this->buildUserPrompt($job, $content),
        ];
    }

    /**
     * @param  array<int,string>  $errors
     * @return array{system:string,user:string}
     */
    public function buildRetryPrompt(AIContentJob $job, string $content, array $errors): array
    {
        $base = $this->build($job, $content);
        $errorLines = implode("\n", array_map(
            static fn (string $error): string => '- '.$error,
            $errors
        ));

        return [
            'system' => $base['system'],
            'user' => trim($base['user'].<<<TEXT

PREVIOUS OUTPUT ISSUES:
{$errorLines}

Return a corrected JSON object that fixes every issue above.
TEXT),
        ];
    }

    private function buildSystemPrompt(AIContentJob $job): string
    {
        $targetLabel = str_replace('_', ' ', $job->target_type->value);

        return trim(<<<TEXT
You are an educational content specialist for Najaah LMS.
Generate {$targetLabel} content that is clear, accurate, concise, and useful for high-school and college learners.
Return a valid JSON object only.
Do not wrap the response in markdown, code fences, or commentary.
Match the requested schema exactly and keep non-text fields typed correctly.
TEXT);
    }

    private function buildUserPrompt(AIContentJob $job, string $content): string
    {
        $targetLabel = str_replace('_', ' ', $job->target_type->value);
        $sourceLabel = str_replace('_', ' ', $job->source_type->value);
        $generationInstructions = $this->generationConfigInstructions($job);
        $schema = $this->outputSchema($job);

        return trim(<<<TEXT
Task:
Generate {$targetLabel} content from the provided {$sourceLabel} source material.

Language requirements:
{$this->languageInstructions($job)}

Generation preferences:
{$generationInstructions}

Output JSON shape:
{$schema}

Source content:
{$content}
TEXT);
    }

    private function languageInstructions(AIContentJob $job): string
    {
        return match ($job->language) {
            'en' => implode("\n", [
                '- Use English for every human-readable field.',
                '- Return plain strings for text fields, not translation objects.',
            ]),
            'both' => implode("\n", [
                '- For every human-readable field, return an object with exactly `ar` and `en` keys.',
                '- Do not return plain strings for titles, descriptions, questions, answers, cards, instructions, or step text.',
                '- Keep numbers, booleans, and enum-like numeric fields as plain scalar values.',
            ]),
            default => implode("\n", [
                '- Use Arabic for every human-readable field.',
                '- Return plain strings for text fields, not translation objects.',
            ]),
        };
    }

    private function generationConfigInstructions(AIContentJob $job): string
    {
        /** @var array<string,mixed> $config */
        $config = is_array($job->generation_config) ? $job->generation_config : [];
        $instructions = [];

        switch ($job->target_type) {
            case AIContentTargetType::Summary:
                if (is_string($config['length'] ?? null)) {
                    $instructions[] = 'Summary length: '.$config['length'].'.';
                }

                if (($config['include_key_points'] ?? null) === true) {
                    $instructions[] = 'Include explicit key points or takeaways.';
                }

                break;

            case AIContentTargetType::Quiz:
                if (is_int($config['question_count'] ?? null)) {
                    $instructions[] = 'Question count: '.$config['question_count'].'.';
                }

                if (is_string($config['difficulty'] ?? null)) {
                    $instructions[] = 'Target difficulty: '.$config['difficulty'].'.';
                }

                if (is_array($config['question_styles'] ?? null) && $config['question_styles'] !== []) {
                    $instructions[] = 'Allowed question styles: '.implode(', ', Arr::wrap($config['question_styles'])).'.';
                }

                break;

            case AIContentTargetType::Assignment:
                if (is_string($config['assignment_style'] ?? null)) {
                    $instructions[] = 'Assignment style: '.$config['assignment_style'].'.';
                }

                if (is_array($config['submission_types'] ?? null) && $config['submission_types'] !== []) {
                    $instructions[] = 'Submission types: '.$this->submissionTypeLabels($config['submission_types']).'.';
                }

                if (is_numeric($config['max_points'] ?? null)) {
                    $instructions[] = 'Maximum points: '.(float) $config['max_points'].'.';
                }

                break;

            case AIContentTargetType::Flashcards:
                if (is_int($config['card_count'] ?? null)) {
                    $instructions[] = 'Card count: '.$config['card_count'].'.';
                }

                if (is_array($config['focus'] ?? null) && $config['focus'] !== []) {
                    $instructions[] = 'Focus areas: '.implode(', ', Arr::wrap($config['focus'])).'.';
                }

                break;

            case AIContentTargetType::InteractiveActivity:
                if ($config !== []) {
                    $encoded = json_encode($config, JSON_UNESCAPED_UNICODE);
                    if (is_string($encoded) && $encoded !== '') {
                        $instructions[] = 'Respect this generation config JSON: '.$encoded;
                    }
                }

                break;
        }

        return $instructions === []
            ? '- Use sensible defaults for depth, structure, and difficulty.'
            : implode("\n", array_map(static fn (string $instruction): string => '- '.$instruction, $instructions));
    }

    private function outputSchema(AIContentJob $job): string
    {
        $schema = $this->baseSchema($job->target_type);
        if ($job->language === 'both') {
            $schema = $this->localizeSchema($schema);
        }

        $encoded = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return is_string($encoded) ? $encoded : '{}';
    }

    /**
     * @return array<string,mixed>
     */
    private function baseSchema(AIContentTargetType $targetType): array
    {
        return match ($targetType) {
            AIContentTargetType::Quiz => [
                'quiz' => [
                    'title' => 'string',
                    'description' => 'string',
                ],
                'questions' => [[
                    'question' => 'string',
                    'options' => [
                        ['text' => 'string', 'is_correct' => true],
                        ['text' => 'string', 'is_correct' => false],
                        ['text' => 'string', 'is_correct' => false],
                        ['text' => 'string', 'is_correct' => false],
                    ],
                    'explanation' => 'string',
                    'points' => 1,
                ]],
            ],
            AIContentTargetType::Assignment => [
                'assignment' => [
                    'title' => 'string',
                    'description' => 'string',
                    'submission_types' => [0, 1, 2],
                    'max_points' => 100,
                    'passing_score' => 60,
                ],
            ],
            AIContentTargetType::Summary => [
                'title' => 'string',
                'content' => 'string',
            ],
            AIContentTargetType::Flashcards => [
                'title' => 'string',
                'cards' => [[
                    'front' => 'string',
                    'back' => 'string',
                ]],
            ],
            AIContentTargetType::InteractiveActivity => [
                'title' => 'string',
                'instructions' => 'string',
                'steps' => [[
                    'title' => 'string',
                    'description' => 'string',
                    'estimated_seconds' => 60,
                ]],
            ],
        };
    }

    private function localizeSchema(mixed $value): mixed
    {
        if (is_string($value)) {
            return [
                'ar' => $value,
                'en' => $value,
            ];
        }

        if (! is_array($value)) {
            return $value;
        }

        $localized = [];
        foreach ($value as $key => $item) {
            $localized[$key] = $this->localizeSchema($item);
        }

        return $localized;
    }

    /**
     * @param  array<int,mixed>  $submissionTypes
     */
    private function submissionTypeLabels(array $submissionTypes): string
    {
        $labels = collect($submissionTypes)
            ->filter(static fn (mixed $type): bool => is_numeric($type))
            ->map(static fn (mixed $type): string => match ((int) $type) {
                0 => 'text',
                1 => 'file',
                2 => 'link',
                default => 'unknown',
            })
            ->filter(static fn (string $label): bool => $label !== 'unknown')
            ->values()
            ->all();

        return $labels === [] ? 'text' : implode(', ', $labels);
    }
}
