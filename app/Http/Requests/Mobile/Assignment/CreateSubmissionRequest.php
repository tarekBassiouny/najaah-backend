<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile\Assignment;

use App\Enums\SubmissionType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->is_student === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'submission_type' => ['sometimes', Rule::enum(SubmissionType::class)],
            'text_content' => ['sometimes', 'nullable', 'string', 'max:50000'],
            'link_url' => ['sometimes', 'nullable', 'url', 'max:2000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function bodyParameters(): array
    {
        return [
            'submission_type' => [
                'description' => 'Submission type: 0=file, 1=text, 2=link.',
                'example' => 1,
            ],
            'text_content' => [
                'description' => 'Text answer content (required by UI when submission_type is text).',
                'example' => 'My assignment answer goes here.',
            ],
            'link_url' => [
                'description' => 'External URL answer (required by UI when submission_type is link).',
                'example' => 'https://example.com/my-assignment',
            ],
        ];
    }
}
