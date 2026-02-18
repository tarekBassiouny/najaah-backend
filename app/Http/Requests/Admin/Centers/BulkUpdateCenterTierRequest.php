<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Centers;

use App\Enums\CenterTier;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BulkUpdateCenterTierRequest extends FormRequest
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
        return [
            'tier' => ['required', 'string', 'in:standard,premium,vip'],
            'center_ids' => ['required', 'array', 'min:1'],
            'center_ids.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null)
    {
        /** @var array<string, mixed> $data */
        $data = parent::validated();

        if (is_string($data['tier'] ?? null)) {
            $data['tier'] = $this->resolveTier($data['tier']);
        }

        if ($key !== null) {
            return data_get($data, $key, $default);
        }

        return $data;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'tier' => [
                'description' => 'Target center tier.',
                'example' => 'premium',
            ],
            'center_ids' => [
                'description' => 'Center IDs to update.',
                'example' => [1, 2, 3],
            ],
            'center_ids.*' => [
                'description' => 'Center ID.',
                'example' => 1,
            ],
        ];
    }

    private function resolveTier(string $tier): CenterTier
    {
        return match ($tier) {
            'premium' => CenterTier::Premium,
            'vip' => CenterTier::Vip,
            default => CenterTier::Standard,
        };
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
