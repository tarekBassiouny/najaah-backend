<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\AIContent;

use App\Enums\AIContentSourceType;
use App\Enums\AIContentTargetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAIContentJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'target_type' => ['required', 'string', Rule::enum(AIContentTargetType::class)],
            'target_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'generation_config' => ['sometimes', 'array'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function bodyParameters(): array
    {
        return [
            'course_id' => [
                'description' => 'Course ID for context and ownership validation.',
                'example' => '12',
            ],
            'source_type' => [
                'description' => 'Content source type: video, pdf, section, course.',
                'example' => 'video',
            ],
            'source_id' => [
                'description' => 'Source entity ID according to source_type.',
                'example' => '34',
            ],
            'target_type' => [
                'description' => 'Target generated content type: quiz, assignment, summary, flashcards, interactive_activity.',
                'example' => 'quiz',
            ],
            'target_id' => [
                'description' => 'Optional existing target entity ID to update/publish into.',
                'example' => '55',
            ],
            'generation_config' => [
                'description' => 'Optional generation options (for example question_count).',
                'example' => '{"question_count": 10}',
            ],
        ];
    }
}
