<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\VideoAccess;

use App\Models\Center;
use App\Services\Settings\PolicySettingsService;
use Illuminate\Foundation\Http\FormRequest;

class CreateVideoCodeBatchRequest extends FormRequest
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
        $center = $this->route('center');
        $policy = $center instanceof Center
            ? $this->policySettingsService()->resolveCenterPolicy($center)
            : [];
        $catalog = $this->policySettingsService()->catalog();
        $maxQuantity = (int) ($policy['video_code_batch_max_quantity'] ?? $catalog['video_code_batch_max_quantity']['default'] ?? 10000);
        $maxViewLimit = (int) ($policy['max_video_code_batch_view_limit'] ?? $catalog['max_video_code_batch_view_limit']['default'] ?? 100);

        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:'.$maxQuantity],
            'view_limit_per_code' => ['sometimes', 'integer', 'min:1', 'max:'.$maxViewLimit],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'quantity' => [
                'description' => 'Number of codes to generate. Must not exceed the center batch quantity limit.',
                'example' => 100,
            ],
            'view_limit_per_code' => [
                'description' => 'Maximum views allowed per code. Defaults to the center video code batch default view limit.',
                'example' => 2,
            ],
        ];
    }

    private function policySettingsService(): PolicySettingsService
    {
        return app(PolicySettingsService::class);
    }
}
