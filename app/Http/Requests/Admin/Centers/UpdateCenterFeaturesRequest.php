<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Centers;

use App\Services\Settings\PolicySettingsService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCenterFeaturesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(
            ['features' => ['required', 'array', 'min:1']],
            $this->policySettingsService()->featureFlagRules('features'),
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'features' => [
                'description' => 'Feature flags to enable or disable for this center.',
                'example' => [
                    'ai_content' => true,
                    'codes_access' => true,
                    'whatsapp_bulk' => false,
                    'guest_browsing' => true,
                    'pdf_downloads' => true,
                ],
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $features = $this->input('features');
            if (! is_array($features)) {
                return;
            }

            $allowedKeys = $this->policySettingsService()->featureFlagKeys();

            $invalidKeys = array_diff(array_keys($features), $allowedKeys);
            if (! empty($invalidKeys)) {
                $validator->errors()->add('features', 'Unsupported feature flags: '.implode(', ', $invalidKeys));
            }
        });
    }

    private function policySettingsService(): PolicySettingsService
    {
        return app(PolicySettingsService::class);
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
}
