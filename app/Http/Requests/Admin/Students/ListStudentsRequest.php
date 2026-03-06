<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Students;

use App\Enums\CenterType;
use App\Filters\Admin\StudentFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;
use Illuminate\Validation\Rule;

class ListStudentsRequest extends AdminListRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return array_merge($this->listRules(), [
            'center_id' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'integer', 'in:0,1,2'],
            'type' => ['sometimes', 'string', Rule::in(['branded', 'unbranded', '0', '1'])],
            'search' => ['sometimes', 'string'],
            'student_name' => ['sometimes', 'string', 'max:255'],
            'student_phone' => ['sometimes', 'string', 'max:30'],
            'student_email' => ['sometimes', 'string', 'max:255'],
            'grade_id' => ['sometimes', 'integer'],
            'school_id' => ['sometimes', 'integer'],
            'college_id' => ['sometimes', 'integer'],
            'stage' => ['sometimes', 'integer', 'in:0,1,2,3,4'],
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
                'description' => 'Filter students by center ID (super admin only).',
                'example' => '2',
            ],
            'status' => [
                'description' => 'Filter students by status (0 inactive, 1 active, 2 banned).',
                'example' => '1',
            ],
            'type' => [
                'description' => 'Filter students by center assignment (branded => center_id not null, unbranded => center_id null).',
                'example' => 'branded',
            ],
            'search' => [
                'description' => 'Legacy broad search by name, username, email, or phone.',
                'example' => '010',
            ],
            'student_name' => [
                'description' => 'Search by student name (partial match).',
                'example' => 'Ahmed',
            ],
            'student_phone' => [
                'description' => 'Search by student phone (partial match).',
                'example' => '0101',
            ],
            'student_email' => [
                'description' => 'Search by student email (partial match).',
                'example' => 'student@example.com',
            ],
            'grade_id' => [
                'description' => 'Filter by grade ID.',
                'example' => '5',
            ],
            'school_id' => [
                'description' => 'Filter by school ID.',
                'example' => '12',
            ],
            'college_id' => [
                'description' => 'Filter by college ID.',
                'example' => '3',
            ],
            'stage' => [
                'description' => 'Filter by grade stage (0..4).',
                'example' => '2',
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

    public function filters(): StudentFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new StudentFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            centerId: FilterInput::intOrNull($data, 'center_id'),
            status: FilterInput::intOrNull($data, 'status'),
            search: FilterInput::stringOrNull($data, 'search'),
            studentName: FilterInput::stringOrNull($data, 'student_name'),
            studentPhone: FilterInput::stringOrNull($data, 'student_phone'),
            studentEmail: FilterInput::stringOrNull($data, 'student_email'),
            gradeId: FilterInput::intOrNull($data, 'grade_id'),
            schoolId: FilterInput::intOrNull($data, 'school_id'),
            collegeId: FilterInput::intOrNull($data, 'college_id'),
            stage: FilterInput::intOrNull($data, 'stage'),
            centerType: $this->resolveType($data)
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveType(array $data): ?int
    {
        $type = FilterInput::stringOrNull($data, 'type');
        if ($type === null) {
            return null;
        }

        return match ($type) {
            'branded' => CenterType::Branded->value,
            'unbranded' => CenterType::Unbranded->value,
            default => is_numeric($type) ? (int) $type : null,
        };
    }
}
