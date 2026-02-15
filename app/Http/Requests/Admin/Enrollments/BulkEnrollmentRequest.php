<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Enrollments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', Rule::exists('users', 'id')],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function queryParameters(): array
    {
        return [
            'course_id' => [
                'description' => 'Course ID to enroll students into.',
                'example' => '12',
            ],
            'user_ids' => [
                'description' => 'Array of student user IDs.',
                'example' => [101, 102, 103],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'course_id' => [
                'description' => 'Course ID to enroll students into.',
                'example' => 12,
            ],
            'user_ids' => [
                'description' => 'Array of student user IDs.',
                'example' => [101, 102, 103],
            ],
        ];
    }
}
