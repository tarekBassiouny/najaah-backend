<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile\Education;

use Illuminate\Foundation\Http\FormRequest;

class ListMobileCollegesRequest extends FormRequest
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
            'search' => ['sometimes', 'string'],
        ];
    }
}
