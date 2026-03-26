<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\VideoAccess;

use App\Models\Center;
use App\Models\VideoCodeBatch;
use App\Services\Settings\PolicySettingsService;
use Illuminate\Foundation\Http\FormRequest;

class ExpandVideoCodeBatchRequest extends FormRequest
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
        $batch = $this->route('batch');
        $center = $this->route('center');

        if (! $center instanceof Center && $batch instanceof VideoCodeBatch) {
            $center = $batch->relationLoaded('center') ? $batch->center : $batch->center()->first();
        }

        $policy = $center instanceof Center
            ? $this->policySettingsService()->resolveCenterPolicy($center)
            : [];
        $catalog = $this->policySettingsService()->catalog();
        $maxQuantity = (int) ($policy['video_code_batch_max_quantity'] ?? $catalog['video_code_batch_max_quantity']['default'] ?? 10000);
        $currentQuantity = $batch instanceof VideoCodeBatch ? (int) $batch->quantity : 0;
        $remainingQuantity = max(0, $maxQuantity - $currentQuantity);

        return [
            'additional_quantity' => ['required', 'integer', 'min:1', 'max:'.$remainingQuantity],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'additional_quantity' => [
                'description' => 'Number of additional codes to generate. Must keep the batch total within the center batch quantity limit.',
                'example' => 50,
            ],
        ];
    }

    private function policySettingsService(): PolicySettingsService
    {
        return app(PolicySettingsService::class);
    }
}
