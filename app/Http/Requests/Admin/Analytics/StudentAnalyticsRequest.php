<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Analytics;

use App\Filters\Admin\AnalyticsStudentFilters;
use App\Support\Filters\FilterInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

final class StudentAnalyticsRequest extends FormRequest
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
        return [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('is_student', true),
            ],
            'center_id' => ['sometimes', 'integer', 'exists:centers,id'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'timezone' => ['sometimes', 'string', 'timezone'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'student_id' => [
                'description' => 'Student ID to retrieve analytics for.',
                'example' => '42',
            ],
            'center_id' => [
                'description' => 'Filter analytics by center ID (super admin only).',
                'example' => '12',
            ],
            'from' => [
                'description' => 'Start date (YYYY-MM-DD).',
                'example' => '2026-01-01',
            ],
            'to' => [
                'description' => 'End date (YYYY-MM-DD).',
                'example' => '2026-01-31',
            ],
            'timezone' => [
                'description' => 'Timezone for date filters (default UTC).',
                'example' => 'UTC',
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

    public function filters(): AnalyticsStudentFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        $timezone = FilterInput::stringOrNull($data, 'timezone') ?? 'UTC';
        $now = Carbon::now($timezone);

        $from = isset($data['from'])
            ? Carbon::parse((string) $data['from'], $timezone)->startOfDay()
            : $now->copy()->subDays(30)->startOfDay();

        $to = isset($data['to'])
            ? Carbon::parse((string) $data['to'], $timezone)->endOfDay()
            : $now->copy()->endOfDay();

        return new AnalyticsStudentFilters(
            studentId: (int) $data['student_id'],
            centerId: FilterInput::intOrNull($data, 'center_id'),
            from: $from->copy()->utc(),
            to: $to->copy()->utc(),
            timezone: $timezone
        );
    }
}
