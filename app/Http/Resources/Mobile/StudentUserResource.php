<?php

declare(strict_types=1);

namespace App\Http\Resources\Mobile;

use App\Enums\UserStatus;
use App\Models\Center;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

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
        $status = $user->status instanceof UserStatus
            ? $user->status
            : ($user->status !== null ? UserStatus::tryFrom((int) $user->status) : null);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'status' => $status?->value ?? $user->status,
            'status_key' => $status !== null ? Str::snake($status->name) : null,
            'status_label' => $status?->name,
            'center' => $user->center instanceof Center
                ? new CenterResource($user->center)
                : null,
            'device' => $user->relationLoaded('activeDevice') && $user->activeDevice
                ? new DeviceResource($user->activeDevice)
                : null,
        ];
    }
}
