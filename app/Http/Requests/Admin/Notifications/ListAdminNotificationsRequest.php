<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Notifications;

use App\Enums\AdminNotificationType;
use App\Filters\Admin\AdminNotificationFilters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAdminNotificationsRequest extends FormRequest
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
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'unread_only' => ['sometimes', 'boolean'],
            'type' => ['sometimes', 'integer', Rule::enum(AdminNotificationType::class)],
            'since' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function filters(): AdminNotificationFilters
    {
        return new AdminNotificationFilters(
            page: (int) $this->input('page', 1),
            perPage: (int) $this->input('per_page', 15),
            unreadOnly: (bool) $this->input('unread_only', false),
            type: $this->has('type') ? AdminNotificationType::from((int) $this->input('type')) : null,
            since: $this->has('since') ? (int) $this->input('since') : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function bodyParameters(): array
    {
        return [
            'page' => [
                'description' => 'Page number for pagination.',
                'example' => 1,
            ],
            'per_page' => [
                'description' => 'Number of items per page (max 100).',
                'example' => 15,
            ],
            'unread_only' => [
                'description' => 'Filter to show only unread notifications.',
                'example' => true,
            ],
            'type' => [
                'description' => 'Filter by notification type (1=System Alert, 2=Device Change, etc.).',
                'example' => 2,
            ],
            'since' => [
                'description' => 'Unix timestamp to get notifications created after this time (for polling).',
                'example' => 1708123456,
            ],
        ];
    }
}
