<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

use App\Models\Center;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class StudentUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'status' => $user->status,
            'center' => $user->center instanceof Center
                ? new CenterResource($user->center)
                : null,
            'device' => $user->relationLoaded('activeDevice') && $user->activeDevice
                ? new DeviceResource($user->activeDevice)
                : null,
        ];
    }
}
