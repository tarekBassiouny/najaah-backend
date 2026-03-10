<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\LandingPages;

use App\Models\CenterLandingPage;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateLayoutVariantsRequest extends FormRequest
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
            'section_layouts' => ['required', 'array', 'min:1'],
            'section_layouts.*' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'section_layouts' => [
                'description' => 'Map of section to layout variant key. Supports partial updates.',
                'example' => ['hero' => 'split', 'testimonials' => 'cards'],
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $layouts = $this->input('section_layouts');

            if (! is_array($layouts)) {
                return;
            }

            foreach ($layouts as $section => $variant) {
                if (! is_string($section) || ! array_key_exists($section, CenterLandingPage::ALLOWED_LAYOUT_VARIANTS)) {
                    $validator->errors()->add('section_layouts.'.$section, 'Unsupported section key.');

                    continue;
                }

                if (! is_string($variant) || ! in_array($variant, CenterLandingPage::ALLOWED_LAYOUT_VARIANTS[$section], true)) {
                    $validator->errors()->add(
                        'section_layouts.'.$section,
                        'Invalid layout variant. Allowed: '.implode(', ', CenterLandingPage::ALLOWED_LAYOUT_VARIANTS[$section]).'.'
                    );
                }
            }
        });
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
