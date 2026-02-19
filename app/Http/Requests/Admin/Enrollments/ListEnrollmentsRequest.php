<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Enrollments;

use App\Filters\Admin\EnrollmentFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;

class ListEnrollmentsRequest extends AdminListRequest
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
            'course_id' => ['sometimes', 'integer'],
            'user_id' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'string'],
        ]);
    }

    public function filters(): EnrollmentFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new EnrollmentFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            centerId: null,
            courseId: FilterInput::intOrNull($data, 'course_id'),
            userId: FilterInput::intOrNull($data, 'user_id'),
            status: FilterInput::stringOrNull($data, 'status')
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
            'course_id' => [
                'description' => 'Filter enrollments by course ID.',
                'example' => '10',
            ],
            'user_id' => [
                'description' => 'Filter enrollments by user ID.',
                'example' => '5',
            ],
            'status' => [
                'description' => 'Filter by enrollment status.',
                'example' => 'ACTIVE',
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
