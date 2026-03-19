<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile;

use App\Enums\AIContentTargetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListCourseAssetsRequest extends FormRequest
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
            'type' => ['sometimes', 'string', Rule::enum(AIContentTargetType::class)],
        ];
    }
}
