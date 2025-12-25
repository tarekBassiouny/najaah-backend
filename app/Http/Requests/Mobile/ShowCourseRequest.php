<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class ShowCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
