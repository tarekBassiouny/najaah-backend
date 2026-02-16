<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AdminNotification
 */
class AdminNotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'type_label_translations' => $this->type->labelTranslations(),
            'type_icon' => $this->type->icon(),
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
            'is_read' => $this->isRead(),
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
