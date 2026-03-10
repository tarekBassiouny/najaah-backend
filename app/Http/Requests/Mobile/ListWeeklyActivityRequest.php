<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class ListWeeklyActivityRequest extends FormRequest
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
            'days' => ['sometimes', 'integer', 'min:1', 'max:30'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'days' => [
                'description' => 'Number of days to include in activity series (1..30). Default: 7.',
                'example' => '7',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function bodyParameters(): array
    {
        return [];
    }
}
