<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\LandingPages;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateAboutSectionRequest extends FormRequest
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
            'about_title' => ['sometimes', 'nullable', 'array'],
            'about_title.en' => ['sometimes', 'nullable', 'string', 'max:255'],
            'about_title.ar' => ['sometimes', 'nullable', 'string', 'max:255'],
            'about_content' => ['sometimes', 'nullable', 'array'],
            'about_content.en' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'about_content.ar' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'about_image_url' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'about_title' => [
                'description' => 'About section title translations.',
                'example' => ['en' => 'About Us', 'ar' => 'معلومات عنا'],
            ],
            'about_content' => [
                'description' => 'About section content translations.',
                'example' => ['en' => 'We are a leading education center...', 'ar' => 'نحن مركز تعليمي رائد...'],
            ],
            'about_image_url' => [
                'description' => 'About section image URL.',
                'example' => 'https://example.com/about.jpg',
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
