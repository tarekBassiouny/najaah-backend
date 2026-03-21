<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\AI;

use App\Services\Centers\CenterScopeService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCenterAIProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        if (! $this->isSystemAdmin()) {
            return [
                'default_model' => ['sometimes', 'nullable', 'string', 'max:120'],
            ];
        }

        return [
            'is_enabled' => ['sometimes', 'boolean'],
            'allowed_models' => ['sometimes', 'nullable', 'array'],
            'allowed_models.*' => ['required_with:allowed_models', 'string', 'max:120'],
            'default_model' => ['sometimes', 'nullable', 'string', 'max:120'],
            'limits' => ['sometimes', 'array'],
            'limits.daily_job_limit' => ['sometimes', 'integer', 'min:0'],
            'limits.monthly_job_limit' => ['sometimes', 'integer', 'min:0'],
            'limits.daily_token_limit' => ['sometimes', 'integer', 'min:0'],
            'limits.monthly_token_limit' => ['sometimes', 'integer', 'min:0'],
            'limits.max_input_chars' => ['sometimes', 'integer', 'min:0'],
            'limits.max_output_chars' => ['sometimes', 'integer', 'min:0'],
            'limits.max_concurrent_jobs' => ['sometimes', 'integer', 'min:0'],
            'limits.default_output_token_estimate' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'is_enabled' => [
                'description' => 'Enable or disable provider for this center.',
                'example' => true,
            ],
            'allowed_models' => [
                'description' => 'Optional model allowlist for this center. Null/omitted uses system models.',
                'example' => ['gpt-4o-mini'],
            ],
            'default_model' => [
                'description' => 'Default model for this center/provider.',
                'example' => 'gpt-4o-mini',
            ],
            'limits' => [
                'description' => 'Per-center runtime limits (all fields optional).',
                'example' => [
                    'daily_job_limit' => 200,
                    'monthly_job_limit' => 3000,
                    'daily_token_limit' => 1200000,
                    'monthly_token_limit' => 30000000,
                    'max_input_chars' => 60000,
                    'max_output_chars' => 30000,
                    'max_concurrent_jobs' => 10,
                ],
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->isSystemAdmin()) {
                return;
            }

            $limits = $this->input('limits');
            if (! is_array($limits)) {
                return;
            }

            $allowedKeys = [
                'daily_job_limit',
                'monthly_job_limit',
                'daily_token_limit',
                'monthly_token_limit',
                'max_input_chars',
                'max_output_chars',
                'max_concurrent_jobs',
                'default_output_token_estimate',
            ];

            $invalidKeys = array_diff(array_keys($limits), $allowedKeys);
            if ($invalidKeys !== []) {
                $validator->errors()->add('limits', 'Unsupported limits keys: '.implode(', ', $invalidKeys));
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $value = $this->input('is_enabled');
        if (is_string($value)) {
            $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($normalized !== null) {
                $this->merge(['is_enabled' => $normalized]);
            }
        }
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

    private function isSystemAdmin(): bool
    {
        $user = $this->user();

        return $user !== null && app(CenterScopeService::class)->isSystemSuperAdmin($user);
    }
}
