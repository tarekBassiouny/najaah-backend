<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Surveys;

use App\Enums\SurveyAssignableType;
use App\Enums\SurveyQuestionType;
use App\Enums\SurveyScopeType;
use App\Enums\SurveyType;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        if (! $user instanceof User) {
            return false;
        }

        $scopeType = (int) $this->input('scope_type');

        if ($scopeType === SurveyScopeType::System->value) {
            return $user->hasRole('super_admin');
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scope_type' => ['required', 'integer', Rule::in(array_column(SurveyScopeType::cases(), 'value'))],
            'center_id' => ['nullable', 'integer', 'exists:centers,id'],
            'title_translations' => ['required', 'array'],
            'title_translations.en' => ['required', 'string', 'max:255'],
            'title_translations.ar' => ['required', 'string', 'max:255'],
            'description_translations' => ['nullable', 'array'],
            'description_translations.en' => ['nullable', 'string'],
            'description_translations.ar' => ['nullable', 'string'],
            'type' => ['required', 'integer', Rule::in(array_column(SurveyType::cases(), 'value'))],
            'is_active' => ['sometimes', 'boolean'],
            'is_mandatory' => ['sometimes', 'boolean'],
            'allow_multiple_submissions' => ['sometimes', 'boolean'],
            'start_at' => ['nullable', 'date_format:Y-m-d'],
            'end_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_at'],

            'questions' => ['required', 'array', 'min:1', 'max:10'],
            'questions.*.question_translations' => ['required', 'array'],
            'questions.*.question_translations.en' => ['required', 'string'],
            'questions.*.question_translations.ar' => ['required', 'string'],
            'questions.*.type' => ['required', 'integer', Rule::in(array_column(SurveyQuestionType::cases(), 'value'))],
            'questions.*.is_required' => ['sometimes', 'boolean'],
            'questions.*.order_index' => ['sometimes', 'integer', 'min:0'],

            'questions.*.options' => ['sometimes', 'array'],
            'questions.*.options.*.option_translations' => ['required_with:questions.*.options', 'array'],
            'questions.*.options.*.option_translations.en' => ['required_with:questions.*.options', 'string'],
            'questions.*.options.*.option_translations.ar' => ['required_with:questions.*.options', 'string'],
            'questions.*.options.*.order_index' => ['sometimes', 'integer', 'min:0'],

            'assignments' => ['sometimes', 'array'],
            'assignments.*.type' => ['required', 'string', Rule::in(array_column(SurveyAssignableType::cases(), 'value'))],
            'assignments.*.id' => ['nullable', 'integer', 'required_unless:assignments.*.type,all'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $scopeType = (int) $this->input('scope_type');
            $centerId = $this->input('center_id');

            if ($scopeType === SurveyScopeType::Center->value && empty($centerId)) {
                /** @var User|null $user */
                $user = $this->user();
                if ($user instanceof User && ! $user->hasRole('super_admin') && empty($user->center_id)) {
                    $validator->errors()->add('center_id', 'Center ID is required for center-scoped surveys.');
                }
            }

            $questions = $this->input('questions', []);
            foreach ($questions as $index => $question) {
                $type = SurveyQuestionType::tryFrom((int) ($question['type'] ?? 0));
                if ($type !== null && $type->requiresOptions()) {
                    $options = $question['options'] ?? [];
                    if (empty($options)) {
                        $validator->errors()->add(
                            sprintf('questions.%s.options', $index),
                            'Options are required for single choice and multiple choice questions.'
                        );
                    }
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
            'scope_type' => [
                'description' => 'Survey scope: 1=System, 2=Center',
                'example' => 2,
            ],
            'center_id' => [
                'description' => 'Center ID (required for CENTER scope)',
                'example' => 1,
            ],
            'title_translations' => [
                'description' => 'Survey title in different languages',
                'example' => ['en' => 'Customer Satisfaction', 'ar' => 'رضا العملاء'],
            ],
            'type' => [
                'description' => 'Survey type: 1=Feedback, 2=Mandatory, 3=Poll',
                'example' => 1,
            ],
            'start_at' => [
                'description' => 'Survey start date (YYYY-MM-DD).',
                'example' => '2026-02-10',
            ],
            'end_at' => [
                'description' => 'Survey end date (YYYY-MM-DD).',
                'example' => '2052-03-05',
            ],
            'questions' => [
                'description' => 'Array of survey questions',
                'example' => [
                    [
                        'question_translations' => ['en' => 'How satisfied are you?', 'ar' => 'ما مدى رضاك؟'],
                        'type' => 3,
                        'is_required' => true,
                    ],
                ],
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

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'FORBIDDEN',
                'message' => 'You are not authorized to create this type of survey.',
            ],
        ], 403));
    }
}
