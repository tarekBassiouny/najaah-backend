<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\LandingPages;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateVisibilitySectionRequest extends FormRequest
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
            'show_hero' => ['sometimes', 'boolean'],
            'show_about' => ['sometimes', 'boolean'],
            'show_courses' => ['sometimes', 'boolean'],
            'show_testimonials' => ['sometimes', 'boolean'],
            'show_contact' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'show_hero' => [
                'description' => 'Whether to show the hero section.',
                'example' => true,
            ],
            'show_about' => [
                'description' => 'Whether to show the about section.',
                'example' => true,
            ],
            'show_courses' => [
                'description' => 'Whether to show the courses section.',
                'example' => true,
            ],
            'show_testimonials' => [
                'description' => 'Whether to show the testimonials section.',
                'example' => true,
            ],
            'show_contact' => [
                'description' => 'Whether to show the contact section.',
                'example' => true,
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
