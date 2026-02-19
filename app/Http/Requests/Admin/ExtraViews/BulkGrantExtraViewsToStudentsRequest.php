<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\ExtraViews;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BulkGrantExtraViewsToStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'distinct'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'video_id' => ['required', 'integer', 'exists:videos,id'],
            'granted_views' => ['required', 'integer', 'min:1'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'decision_reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'student_ids' => [
                'description' => 'Student IDs to grant extra views for.',
                'example' => [10, 11, 12],
            ],
            'course_id' => [
                'description' => 'Course ID the video belongs to.',
                'example' => 42,
            ],
            'video_id' => [
                'description' => 'Video ID to grant extra views for.',
                'example' => 99,
            ],
            'granted_views' => [
                'description' => 'Number of extra views to grant per student.',
                'example' => 2,
            ],
            'reason' => [
                'description' => 'Optional reason attached to created records.',
                'example' => 'Manual support grant',
            ],
            'decision_reason' => [
                'description' => 'Optional admin decision note.',
                'example' => 'Granted after support review.',
            ],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Validation failed',
                'details' => $validator->errors(),
            ],
        ], 422));
    }
}
