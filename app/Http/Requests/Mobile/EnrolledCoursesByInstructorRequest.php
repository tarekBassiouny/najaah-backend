<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile;

use App\Filters\Mobile\CourseFilters;
use Illuminate\Foundation\Http\FormRequest;

class EnrolledCoursesByInstructorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'category_id' => ['sometimes', 'integer'],
        ];
    }

    public function filters(): CourseFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new CourseFilters(
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 15),
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            instructorId: null,
            enrolled: true,
            isFeatured: null,
            publishFrom: null,
            publishTo: null
        );
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'per_page' => [
                'description' => 'Items per page (max 100).',
                'example' => '15',
            ],
            'page' => [
                'description' => 'Page number to retrieve.',
                'example' => '1',
            ],
            'category_id' => [
                'description' => 'Filter courses by category ID.',
                'example' => '3',
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [];
    }
}
