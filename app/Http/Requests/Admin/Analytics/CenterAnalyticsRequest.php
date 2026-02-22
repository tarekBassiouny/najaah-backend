<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Analytics;

use App\Filters\Admin\AnalyticsFilters;
use App\Models\Center;
use App\Support\Filters\FilterInput;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

final class CenterAnalyticsRequest extends FormRequest
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
            'center_id' => [
                'description' => 'Optional center filter. If provided, must match the route center.',
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

    public function filters(): AnalyticsFilters
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

        return new AnalyticsFilters(
            centerId: $this->getRouteCenterId(),
            from: $from->copy()->utc(),
            to: $to->copy()->utc(),
            timezone: $timezone
        );
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $routeCenterId = $this->getRouteCenterId();

            if ($routeCenterId === null || ! $this->has('center_id')) {
                return;
            }

            $centerId = FilterInput::intOrNull($this->all(), 'center_id');
            if ($centerId !== null && $centerId !== $routeCenterId) {
                $validator->errors()->add('center_id', 'Center ID must match the route center.');
            }
        });
    }

    private function getRouteCenterId(): ?int
    {
        $routeCenter = $this->route('center');

        if ($routeCenter instanceof Center) {
            return (int) $routeCenter->id;
        }

        if (is_numeric($routeCenter)) {
            return (int) $routeCenter;
        }

        return null;
    }
}
