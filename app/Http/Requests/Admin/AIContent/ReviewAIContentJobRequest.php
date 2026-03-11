<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\AIContent;

use Illuminate\Foundation\Http\FormRequest;

class ReviewAIContentJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'reviewed_payload' => ['required', 'array'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function bodyParameters(): array
    {
        return [
            'reviewed_payload' => [
                'description' => 'Admin-edited payload to approve and publish.',
                'example' => '{"title":"Edited title","content":"Edited content"}',
            ],
        ];
    }
}
