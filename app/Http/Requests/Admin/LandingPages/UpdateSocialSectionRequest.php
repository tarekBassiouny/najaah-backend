<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\LandingPages;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateSocialSectionRequest extends FormRequest
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
            'social_facebook' => ['sometimes', 'nullable', 'string', 'max:500'],
            'social_twitter' => ['sometimes', 'nullable', 'string', 'max:500'],
            'social_instagram' => ['sometimes', 'nullable', 'string', 'max:500'],
            'social_youtube' => ['sometimes', 'nullable', 'string', 'max:500'],
            'social_linkedin' => ['sometimes', 'nullable', 'string', 'max:500'],
            'social_tiktok' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'social_facebook' => [
                'description' => 'Facebook page URL.',
                'example' => 'https://facebook.com/yourcenter',
            ],
            'social_twitter' => [
                'description' => 'Twitter/X profile URL.',
                'example' => 'https://twitter.com/yourcenter',
            ],
            'social_instagram' => [
                'description' => 'Instagram profile URL.',
                'example' => 'https://instagram.com/yourcenter',
            ],
            'social_youtube' => [
                'description' => 'YouTube channel URL.',
                'example' => 'https://youtube.com/@yourcenter',
            ],
            'social_linkedin' => [
                'description' => 'LinkedIn page URL.',
                'example' => 'https://linkedin.com/company/yourcenter',
            ],
            'social_tiktok' => [
                'description' => 'TikTok profile URL.',
                'example' => 'https://tiktok.com/@yourcenter',
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
