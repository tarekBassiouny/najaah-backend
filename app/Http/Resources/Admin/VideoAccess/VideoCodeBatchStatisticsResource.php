<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\VideoAccess;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoCodeBatchStatisticsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;

        return [
            'batch_id' => $data['batch_id'],
            'batch_code' => $data['batch_code'],
            'video_id' => $data['video_id'],
            'course_id' => $data['course_id'],
            'quantity' => $data['quantity'],
            'total_codes' => $data['total_codes'],
            'sold_limit' => $data['sold_limit'],
            'redeemed_count' => $data['redeemed_count'],
            'available_count' => $data['available_count'],
            'remaining' => $data['remaining'],
            'status' => $data['status'],
            'is_open' => $data['is_open'],
            'first_redemption_at' => $data['first_redemption_at'],
            'last_redemption_at' => $data['last_redemption_at'],
            'exports' => $data['exports'],
            'recent_redemptions' => $data['recent_redemptions'],
            'redemption_rate' => $this->calculateRedemptionRate($data),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function calculateRedemptionRate(array $data): ?float
    {
        $denominator = $data['sold_limit'] ?? $data['quantity'];
        if ($denominator <= 0) {
            return null;
        }

        return round(($data['redeemed_count'] / $denominator) * 100, 2);
    }
}
