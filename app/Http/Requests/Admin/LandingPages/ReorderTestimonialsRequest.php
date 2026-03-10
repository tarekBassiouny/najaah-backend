<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\LandingPages;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReorderTestimonialsRequest extends FormRequest
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
            'testimonial_ids' => ['required', 'array', 'min:1'],
            'testimonial_ids.*' => ['required', 'integer', 'exists:center_landing_testimonials,id'],
        ];
    }

    /**
     * @return array<int>
     */
    public function getOrderedIds(): array
    {
        /** @var array<int|string> $ids */
        $ids = $this->validated('testimonial_ids') ?? [];

        return array_map('intval', $ids);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'testimonial_ids' => [
                'description' => 'Array of testimonial IDs in the desired order.',
                'example' => [3, 1, 2],
            ],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Validation failed',
                'details' => $validator->errors(),
            ],
        ], 422));
    }
}
