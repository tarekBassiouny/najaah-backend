<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile;

use App\Filters\Mobile\CourseFilters;
use Illuminate\Foundation\Http\FormRequest;

class ExploreCoursesRequest extends FormRequest
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
            'instructor_id' => ['sometimes', 'integer'],
            'enrolled' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'publish_from' => ['sometimes', 'date'],
            'publish_to' => ['sometimes', 'date'],
        ];
    }

    public function filters(): CourseFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        if (array_key_exists('enrolled', $data)) {
            $enrolled = filter_var($data['enrolled'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        if (array_key_exists('is_featured', $data)) {
            $isFeatured = filter_var($data['is_featured'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return new CourseFilters(
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 15),
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            instructorId: isset($data['instructor_id']) ? (int) $data['instructor_id'] : null,
            enrolled: $enrolled ?? null,
            isFeatured: $isFeatured ?? null,
            publishFrom: isset($data['publish_from']) ? (string) $data['publish_from'] : null,
            publishTo: isset($data['publish_to']) ? (string) $data['publish_to'] : null
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
            'instructor_id' => [
                'description' => 'Filter courses by instructor ID.',
                'example' => '5',
            ],
            'enrolled' => [
                'description' => 'Filter by enrollment status.',
                'example' => 'true',
            ],
            'is_featured' => [
                'description' => 'Filter by featured flag.',
                'example' => '0',
            ],
            'publish_from' => [
                'description' => 'Filter courses published on or after this date.',
                'example' => '2025-01-01',
            ],
            'publish_to' => [
                'description' => 'Filter courses published on or before this date.',
                'example' => '2025-01-31',
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
