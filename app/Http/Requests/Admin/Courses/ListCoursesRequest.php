<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Courses;

use App\Filters\Admin\CourseFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;

class ListCoursesRequest extends AdminListRequest
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
        return array_merge($this->listRules(), [
            'center_id' => ['sometimes', 'integer'],
            'category_id' => ['sometimes', 'integer'],
            'primary_instructor_id' => ['sometimes', 'integer'],
            'search' => ['sometimes', 'string'],
        ]);
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
            'center_id' => [
                'description' => 'Filter courses by center ID (super admin only).',
                'example' => '2',
            ],
            'category_id' => [
                'description' => 'Filter courses by category ID.',
                'example' => '3',
            ],
            'primary_instructor_id' => [
                'description' => 'Filter courses by primary instructor ID.',
                'example' => '5',
            ],
            'search' => [
                'description' => 'Search courses by title.',
                'example' => 'Biology',
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

    public function filters(): CourseFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new CourseFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            centerId: FilterInput::intOrNull($data, 'center_id'),
            categoryId: FilterInput::intOrNull($data, 'category_id'),
            primaryInstructorId: FilterInput::intOrNull($data, 'primary_instructor_id'),
            search: FilterInput::stringOrNull($data, 'search')
        );
    }
}
