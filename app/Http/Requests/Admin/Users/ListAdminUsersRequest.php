<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Users;

use App\Filters\Admin\AdminUserFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Models\Center;
use App\Support\Filters\FilterInput;
use Illuminate\Contracts\Validation\Validator;

class ListAdminUsersRequest extends AdminListRequest
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
            'center_id' => ['sometimes', 'integer', 'exists:centers,id'],
        ]);
    }

    public function filters(): AdminUserFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new AdminUserFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
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

    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'center_id' => [
                'description' => 'Optional center filter on system route. On center route, if provided, it must match route center.',
                'example' => '12',
            ],
            'per_page' => [
                'description' => 'Items per page (max 100).',
                'example' => '15',
            ],
            'page' => [
                'description' => 'Page number to retrieve.',
                'example' => '1',
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
