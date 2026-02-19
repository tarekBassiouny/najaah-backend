<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Enrollments;

use App\Filters\Admin\EnrollmentFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;

class ListEnrollmentsRequest extends AdminListRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('status')) {
            return;
        }

        $this->merge([
            'status' => strtoupper(trim((string) $this->input('status'))),
        ]);
    }

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
            'user_id' => ['sometimes', 'integer'],
            'search' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:ACTIVE,DEACTIVATED,CANCELLED,PENDING'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date'],
        ]);
    }

    public function filters(): EnrollmentFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new EnrollmentFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            centerId: FilterInput::intOrNull($data, 'center_id'),
            courseId: FilterInput::intOrNull($data, 'course_id'),
            userId: FilterInput::intOrNull($data, 'user_id'),
            search: FilterInput::stringOrNull($data, 'search'),
            status: FilterInput::stringOrNull($data, 'status'),
            dateFrom: FilterInput::stringOrNull($data, 'date_from'),
            dateTo: FilterInput::stringOrNull($data, 'date_to')
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
            'center_id' => [
                'description' => 'Filter enrollments by center ID (system scope only).',
                'example' => '2',
            ],
            'course_id' => [
                'description' => 'Filter enrollments by course ID.',
                'example' => '10',
            ],
            'user_id' => [
                'description' => 'Filter enrollments by user ID.',
                'example' => '5',
            ],
            'search' => [
                'description' => 'Search by student name, phone, or email (partial match).',
                'example' => '0101',
            ],
            'status' => [
                'description' => 'Filter by enrollment status.',
                'example' => 'ACTIVE',
            ],
            'date_from' => [
                'description' => 'Filter enrollments created from this date.',
                'example' => '2025-01-01',
            ],
            'date_to' => [
                'description' => 'Filter enrollments created up to this date.',
                'example' => '2025-12-31',
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
