<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\AIContent;

use App\Enums\AIContentSourceType;
use App\Enums\AIContentTargetType;
use App\Http\Requests\Admin\AIContent\Concerns\ValidatesAIContentGenerationConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreateAIContentBatchRequest extends FormRequest
{
    use ValidatesAIContentGenerationConfig;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('language')) {
            $this->merge([
                'language' => 'ar',
            ]);
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'source_type' => ['required', 'string', Rule::enum(AIContentSourceType::class)],
            'source_id' => ['required', 'integer', 'min:1'],
            'language' => ['required', 'string', Rule::in(['en', 'ar', 'both'])],
            'assets' => ['required', 'array', 'min:1', 'max:4'],
            'assets.*.target_type' => ['required', 'string', Rule::in([
                AIContentTargetType::Summary->value,
                AIContentTargetType::Quiz->value,
                AIContentTargetType::Flashcards->value,
                AIContentTargetType::Assignment->value,
                AIContentTargetType::InteractiveActivity->value,
            ])],
            'assets.*.target_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'assets.*.generation_config' => ['sometimes', 'array'],
            'assets.*.ai_provider' => ['sometimes', 'string', 'max:50'],
            'assets.*.ai_model' => ['sometimes', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var array<int,array<string,mixed>> $assets */
            $assets = $this->input('assets', []);
            $targetTypes = array_map(
                static fn (array $asset): string => (string) ($asset['target_type'] ?? ''),
                $assets
            );

            if (count($targetTypes) !== count(array_unique($targetTypes))) {
                $validator->errors()->add('assets', 'Each target_type may only appear once per batch.');
            }

            foreach ($assets as $index => $asset) {
                $this->validateGenerationConfig(
                    $validator,
                    sprintf('assets.%d.generation_config', $index),
                    (string) ($asset['target_type'] ?? ''),
                    $asset['generation_config'] ?? []
                );
            }
        });
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'course_id' => [
                'description' => 'Course ID for batch ownership and source validation.',
                'example' => 12,
            ],
            'source_type' => [
                'description' => 'Shared source type for the whole batch: video, pdf, section, or course.',
                'example' => AIContentSourceType::Video->value,
            ],
            'source_id' => [
                'description' => 'Shared source entity ID according to source_type.',
                'example' => 34,
            ],
            'language' => [
                'description' => 'Output language preference for all generated assets. Defaults to ar.',
                'example' => 'ar',
            ],
            'assets' => [
                'description' => 'Assets to generate from the same source. Each target_type may appear only once.',
                'example' => [
                    [
                        'target_type' => AIContentTargetType::Summary->value,
                    ],
                    [
                        'target_type' => AIContentTargetType::Quiz->value,
                        'generation_config' => [
                            'question_count' => 10,
                        ],
                    ],
                ],
            ],
            'assets.*.target_type' => [
                'description' => 'Target generated content type.',
                'example' => AIContentTargetType::Quiz->value,
            ],
            'assets.*.target_id' => [
                'description' => 'Optional existing target entity ID to update instead of creating a new one.',
                'example' => 55,
            ],
            'assets.*.generation_config' => [
                'description' => 'Optional target-specific generation options.',
                'example' => [
                    'question_count' => 10,
                ],
            ],
            'assets.*.ai_provider' => [
                'description' => 'Optional provider override for this specific asset in the batch.',
                'example' => 'gemini',
            ],
            'assets.*.ai_model' => [
                'description' => 'Optional model override for this specific asset in the batch.',
                'example' => 'gpt-4o-mini',
            ],
        ];
    }
}
