<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\LandingPages;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateHeroSectionRequest extends FormRequest
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
            'hero_title' => ['sometimes', 'nullable', 'array'],
            'hero_title.en' => ['sometimes', 'nullable', 'string', 'max:255'],
            'hero_title.ar' => ['sometimes', 'nullable', 'string', 'max:255'],
            'hero_subtitle' => ['sometimes', 'nullable', 'array'],
            'hero_subtitle.en' => ['sometimes', 'nullable', 'string', 'max:500'],
            'hero_subtitle.ar' => ['sometimes', 'nullable', 'string', 'max:500'],
            'hero_background_url' => ['sometimes', 'nullable', 'string', 'max:500'],
            'hero_cta_text' => ['sometimes', 'nullable', 'string', 'max:100'],
            'hero_cta_url' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'hero_title' => [
                'description' => 'Hero section title translations.',
                'example' => ['en' => 'Welcome', 'ar' => 'مرحبا'],
            ],
            'hero_subtitle' => [
                'description' => 'Hero section subtitle translations.',
                'example' => ['en' => 'Learn with us', 'ar' => 'تعلم معنا'],
            ],
            'hero_background_url' => [
                'description' => 'Background image URL.',
                'example' => 'https://example.com/hero.jpg',
            ],
            'hero_cta_text' => [
                'description' => 'Call to action button text.',
                'example' => 'Get Started',
            ],
            'hero_cta_url' => [
                'description' => 'Call to action button URL.',
                'example' => '/register',
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
