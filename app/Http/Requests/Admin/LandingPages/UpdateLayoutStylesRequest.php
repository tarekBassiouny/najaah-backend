<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\LandingPages;

use App\Models\CenterLandingPage;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateLayoutStylesRequest extends FormRequest
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
            'section_styles' => ['required', 'array', 'min:1'],
            'section_styles.*' => ['required', 'array'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'section_styles' => [
                'description' => 'Map of section to whitelisted style overrides. Supports partial updates.',
                'example' => [
                    'hero' => ['text_align' => 'left', 'overlay_opacity' => 0.6],
                    'courses' => ['columns_desktop' => 3],
                ],
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $styles = $this->input('section_styles');

            if (! is_array($styles)) {
                return;
            }

            foreach ($styles as $section => $sectionStyles) {
                if (! is_string($section) || ! array_key_exists($section, CenterLandingPage::ALLOWED_STYLE_KEYS)) {
                    $validator->errors()->add('section_styles.'.$section, 'Unsupported section key.');

                    continue;
                }

                if (! is_array($sectionStyles)) {
                    $validator->errors()->add('section_styles.'.$section, 'Styles must be an object.');

                    continue;
                }

                $allowedKeys = CenterLandingPage::ALLOWED_STYLE_KEYS[$section];
                foreach ($sectionStyles as $styleKey => $styleValue) {
                    if (! is_string($styleKey) || ! in_array($styleKey, $allowedKeys, true)) {
                        $validator->errors()->add(
                            'section_styles.'.$section.'.'.$styleKey,
                            'Unsupported style key for this section.'
                        );

                        continue;
                    }

                    if (
                        ! is_string($styleValue)
                        && ! is_int($styleValue)
                        && ! is_float($styleValue)
                        && ! is_bool($styleValue)
                        && $styleValue !== null
                    ) {
                        $validator->errors()->add(
                            'section_styles.'.$section.'.'.$styleKey,
                            'Style value must be a scalar or null.'
                        );
                    }
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
