<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\LandingPages;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateLandingTestimonialRequest extends FormRequest
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
            'author_name' => ['sometimes', 'required', 'string', 'max:255'],
            'author_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'author_image_url' => ['sometimes', 'nullable', 'string', 'max:500'],
            'content' => ['sometimes', 'required', 'array'],
            'content.en' => ['required_with:content', 'string', 'max:2000'],
            'content.ar' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'rating' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'author_name' => [
                'description' => 'Name of the testimonial author.',
                'example' => 'John Doe',
            ],
            'author_title' => [
                'description' => 'Title or role of the author.',
                'example' => 'Student',
            ],
            'author_image_url' => [
                'description' => "URL to the author's avatar image.",
                'example' => 'https://example.com/avatar.jpg',
            ],
            'content' => [
                'description' => 'Testimonial content translations.',
                'example' => ['en' => 'Great experience!', 'ar' => 'تجربة رائعة!'],
            ],
            'rating' => [
                'description' => 'Rating from 1 to 5.',
                'example' => 5,
            ],
            'is_active' => [
                'description' => 'Whether the testimonial is visible.',
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
