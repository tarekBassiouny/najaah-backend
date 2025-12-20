<?php

declare(strict_types=1);

namespace App\Http\Requests\Instructor;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Arr;

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
            'avatar' => ['sometimes', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:512000'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'string'],
            'metadata' => ['sometimes', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $metadata = $this->input('metadata');
            if (! is_array($metadata)) {
                return;
            }

            $allowed = config('instructors.metadata_keys', []);
            $unknown = array_diff(array_keys($metadata), $allowed);
            if (! empty($unknown)) {
                $validator->errors()->add('metadata', 'Unsupported metadata keys: '.implode(', ', $unknown));
            }

            foreach ($metadata as $key => $value) {
                if (is_array($value)) {
                    $invalid = Arr::first($value, static fn ($v): bool => ! is_string($v));
                    if ($invalid !== null) {
                        $validator->errors()->add("metadata.{$key}", 'Metadata arrays must contain only strings.');
                    }
                } elseif (! is_string($value) && ! is_numeric($value)) {
                    $validator->errors()->add("metadata.{$key}", 'Metadata value must be a string, number, or array of strings.');
                }
            }
        });
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
            'avatar' => [
                'description' => 'Profile image file upload.',
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
            'social_links.*' => [
                'description' => 'Social link value.',
                'example' => 'https://linkedin.com/in/johndoe',
            ],
            'metadata' => [
                'description' => 'Optional instructor metadata.',
                'example' => ['specialization' => 'Math', 'languages' => ['en', 'ar']],
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
