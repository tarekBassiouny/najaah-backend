<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Dashboard;

use App\Filters\Admin\DashboardFilters;
use App\Models\Center;
use App\Support\Filters\FilterInput;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class DashboardRequest extends FormRequest
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
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'center_id' => [
                'description' => 'Optional center filter for system dashboard. On center route, if provided, it must match route center.',
                'example' => '12',
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

    public function filters(): DashboardFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new DashboardFilters(
            centerId: FilterInput::intOrNull($data, 'center_id')
        );
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $routeCenter = $this->route('center');
            $routeCenterId = null;

            if ($routeCenter instanceof Center) {
                $routeCenterId = (int) $routeCenter->id;
            } elseif (is_numeric($routeCenter)) {
                $routeCenterId = (int) $routeCenter;
            }

            if ($routeCenterId === null || ! $this->has('center_id')) {
                return;
            }

            $centerId = FilterInput::intOrNull($this->all(), 'center_id');
            if ($centerId !== null && $centerId !== $routeCenterId) {
                $validator->errors()->add('center_id', 'Center ID must match the route center.');
            }
        });
    }
}
