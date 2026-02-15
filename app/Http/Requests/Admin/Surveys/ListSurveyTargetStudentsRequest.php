<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Surveys;

use App\Enums\CenterType;
use App\Enums\SurveyScopeType;
use App\Http\Requests\Admin\AdminListRequest;
use App\Models\Center;
use App\Models\User;
use App\Support\Filters\FilterInput;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ListSurveyTargetStudentsRequest extends AdminListRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        return $user instanceof User;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge($this->listRules(), [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'scope_type' => ['sometimes', 'integer', Rule::in(array_column(SurveyScopeType::cases(), 'value'))],
            'center_id' => ['sometimes', 'nullable', 'integer', 'exists:centers,id'],
            'status' => ['sometimes', 'integer', 'in:0,1,2'],
            'search' => ['sometimes', 'string'],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $routeCenterId = $this->routeCenterId();
            $scopeType = $this->has('scope_type') ? (int) $this->input('scope_type') : null;
            $centerId = FilterInput::intOrNull($this->all(), 'center_id');

            if ($routeCenterId !== null) {
                if ($scopeType !== null && $scopeType !== SurveyScopeType::Center->value) {
                    $validator->errors()->add('scope_type', 'Center routes only accept center-scoped targeting.');
                }

                if ($centerId !== null && $centerId !== $routeCenterId) {
                    $validator->errors()->add('center_id', 'Center ID must match the route center.');
                }

                return;
            }

            if ($scopeType !== null && $scopeType !== SurveyScopeType::System->value) {
                $validator->errors()->add('scope_type', 'System routes only accept system-scoped targeting.');
            }

            if ($centerId === null) {
                return;
            }

            $isUnbranded = Center::query()
                ->whereKey($centerId)
                ->where('type', CenterType::Unbranded->value)
                ->exists();

            if ($isUnbranded) {
                return;
            }

            $validator->errors()->add('center_id', 'System survey targeting supports only unbranded centers.');
        });
    }

    /**
     * @return array{
     *     scope_type: SurveyScopeType,
     *     center_id: int|null,
     *     status: int|null,
     *     search: string|null,
     *     page: int,
     *     per_page: int
     * }
     */
    public function filters(): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();
        $routeCenterId = $this->routeCenterId();
        $scopeType = $routeCenterId !== null ? SurveyScopeType::Center : SurveyScopeType::System;
        $centerId = $routeCenterId ?? FilterInput::intOrNull($data, 'center_id');

        return [
            'scope_type' => $scopeType,
            'center_id' => $centerId,
            'status' => FilterInput::intOrNull($data, 'status'),
            'search' => FilterInput::stringOrNull($data, 'search'),
            'page' => FilterInput::page($data),
            'per_page' => FilterInput::perPage($data),
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'scope_type' => [
                'description' => 'Optional. Must match route scope if provided (1 system, 2 center).',
                'example' => '2',
            ],
            'center_id' => [
                'description' => 'Optional. For center routes it must match route center. For system routes, if provided, it must be an unbranded center.',
                'example' => '10',
            ],
            'status' => [
                'description' => 'Optional student status filter (0 inactive, 1 active, 2 banned).',
                'example' => '1',
            ],
            'search' => [
                'description' => 'Optional search by name, username, email, or phone.',
                'example' => 'ahmed',
            ],
            'per_page' => [
                'description' => 'Items per page (max 50, recommended 20 for infinite scroll).',
                'example' => '20',
            ],
            'page' => [
                'description' => 'Page number.',
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

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Validation failed',
                'details' => $validator->errors(),
            ],
        ], 422));
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'FORBIDDEN',
                'message' => 'You are not authorized to list target students.',
            ],
        ], 403));
    }

    private function routeCenterId(): ?int
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
