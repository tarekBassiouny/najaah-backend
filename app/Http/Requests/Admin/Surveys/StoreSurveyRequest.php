<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Surveys;

use App\Enums\SurveyAssignableType;
use App\Enums\SurveyQuestionType;
use App\Enums\SurveyScopeType;
use App\Enums\SurveyType;
use App\Models\Center;
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

        return $user instanceof User;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scope_type' => ['sometimes', 'integer', Rule::in(array_column(SurveyScopeType::cases(), 'value'))],
            'center_id' => ['sometimes', 'nullable', 'integer', 'exists:centers,id'],
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
            $routeCenterId = $this->routeCenterId();
            $scopeType = $this->has('scope_type') ? (int) $this->input('scope_type') : null;
            $centerIdProvided = $this->has('center_id');
            $centerId = $this->input('center_id');

            if ($routeCenterId !== null) {
                if ($scopeType !== null && $scopeType !== SurveyScopeType::Center->value) {
                    $validator->errors()->add('scope_type', 'Center routes only accept center-scoped surveys.');
                }

                if ($centerIdProvided && (! is_numeric($centerId) || (int) $centerId !== $routeCenterId)) {
                    $validator->errors()->add('center_id', 'Center ID must match the route center.');
                }
            } else {
                if ($scopeType !== null && $scopeType !== SurveyScopeType::System->value) {
                    $validator->errors()->add('scope_type', 'System routes only accept system-scoped surveys.');
                }

                if ($centerIdProvided && $centerId !== null) {
                    $validator->errors()->add('center_id', 'System-scoped surveys must use center_id = null.');
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

    private function routeCenterId(): ?int
    {
        $routeCenter = $this->route('center');

        if ($routeCenter instanceof Center) {
            return (int) $routeCenter->id;
        }

        if (is_numeric($routeCenter)) {
            return (int) $routeCenter;
        }

        return null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'scope_type' => [
                'description' => 'Optional. Must match route scope if provided: system routes accept 1, center routes accept 2.',
                'example' => 2,
            ],
            'center_id' => [
                'description' => 'Optional. For center routes it must match route center. For system routes it must be null.',
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
                'message' => 'You are not authorized to create surveys.',
            ],
        ], 403));
    }
}
