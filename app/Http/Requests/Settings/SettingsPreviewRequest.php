<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SettingsPreviewRequest extends FormRequest
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
            'student_id' => ['nullable', 'integer', 'exists:users,id'],
            'video_id' => ['nullable', 'integer', 'exists:videos,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'center_id' => ['nullable', 'integer', 'exists:centers,id'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'student_id' => [
                'description' => 'Optional student ID for resolution context.',
                'example' => 1,
            ],
            'video_id' => [
                'description' => 'Optional video ID for resolution context.',
                'example' => 2,
            ],
            'course_id' => [
                'description' => 'Optional course ID for resolution context.',
                'example' => 3,
            ],
            'center_id' => [
                'description' => 'Optional center ID for resolution context.',
                'example' => 4,
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasAny = $this->filled('student_id') || $this->filled('video_id') || $this->filled('course_id') || $this->filled('center_id');

            if (! $hasAny) {
                $validator->errors()->add('context', 'At least one context id is required.');
            }
        });
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
