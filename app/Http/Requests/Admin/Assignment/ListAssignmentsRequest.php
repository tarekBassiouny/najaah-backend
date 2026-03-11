<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Assignment;

use Illuminate\Foundation\Http\FormRequest;

class ListAssignmentsRequest extends FormRequest
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
            'active_only' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'active_only' => [
                'description' => 'Filter assignments by active status.',
                'example' => 'true',
            ],
        ];
    }
}
