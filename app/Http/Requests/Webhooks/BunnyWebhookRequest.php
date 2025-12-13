<?php

declare(strict_types=1);

namespace App\Http\Requests\Webhooks;

use Illuminate\Foundation\Http\FormRequest;

class BunnyWebhookRequest extends FormRequest
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
            'Event' => ['required', 'string'],
            'VideoGuid' => ['required', 'string'],
            'LibraryId' => ['required', 'string'],
            'ErrorMessage' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
