<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\VideoAccess;

use App\Models\VideoCodeRedemption;
use App\Services\VideoAccess\VideoCodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VideoCodeRedemption
 */
class VideoCodeBatchRedemptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->loadMissing(['user', 'batch']);

        $phone = null;
        if (is_string($this->user->phone) && $this->user->phone !== '') {
            $phone = ($this->user->country_code ?? '').$this->user->phone;
        }

        return [
            'id' => $this->id,
            'sequence_number' => $this->sequence_number,
            'code' => app(VideoCodeGenerator::class)->generateCode($this->batch, $this->sequence_number),
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'phone' => $phone,
            ] : null,
            'redeemed_at' => $this->redeemed_at->toIso8601String(),
        ];
    }
}
