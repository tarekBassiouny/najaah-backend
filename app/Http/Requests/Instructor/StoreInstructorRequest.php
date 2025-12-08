<?php

declare(strict_types=1);

namespace App\Http\Requests\Instructor;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreInstructorRequest extends FormRequest
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
            'center_id' => ['nullable', 'integer', 'exists:centers,id'],
            'name_translations' => ['required', 'array'],
            'bio_translations' => ['nullable', 'array'],
            'title_translations' => ['nullable', 'array'],
            'avatar_url' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'center_id' => [
                'description' => 'Optional center ID that the instructor belongs to.',
                'example' => 1,
            ],
            'name_translations' => [
                'description' => 'Localized instructor name keyed by locale.',
                'example' => ['en' => 'John Doe', 'ar' => 'جون دو'],
            ],
            'bio_translations' => [
                'description' => 'Localized biography.',
                'example' => ['en' => 'Senior instructor', 'ar' => 'مدرب كبير'],
            ],
            'title_translations' => [
                'description' => 'Localized title or position.',
                'example' => ['en' => 'Professor', 'ar' => 'أستاذ'],
            ],
            'avatar_url' => [
                'description' => 'Profile image URL.',
                'example' => 'https://example.com/avatar.jpg',
            ],
            'email' => [
                'description' => 'Contact email for the instructor.',
                'example' => 'john.doe@example.com',
            ],
            'phone' => [
                'description' => 'Contact phone number.',
                'example' => '+1234567890',
            ],
            'social_links' => [
                'description' => 'Optional social/profile links.',
                'example' => ['linkedin' => 'https://linkedin.com/in/johndoe'],
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
