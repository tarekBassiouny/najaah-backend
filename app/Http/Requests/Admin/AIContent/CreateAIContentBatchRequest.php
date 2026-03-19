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
}
