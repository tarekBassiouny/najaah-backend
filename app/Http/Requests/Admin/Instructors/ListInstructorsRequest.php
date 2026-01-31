<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Instructors;

use App\Filters\Admin\InstructorFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;

class ListInstructorsRequest extends AdminListRequest
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
            'course_id' => ['sometimes', 'integer'],
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
                'description' => 'Filter instructors by center ID.',
                'example' => '10',
            ],
            'course_id' => [
                'description' => 'Filter instructors by course ID.',
                'example' => '10',
            ],
            'search' => [
                'description' => 'Search instructors by name.',
                'example' => 'Sara',
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

    public function filters(): InstructorFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new InstructorFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            centerId: FilterInput::intOrNull($data, 'center_id'),
            courseId: FilterInput::intOrNull($data, 'course_id'),
            search: FilterInput::stringOrNull($data, 'search')
        );
    }
}
