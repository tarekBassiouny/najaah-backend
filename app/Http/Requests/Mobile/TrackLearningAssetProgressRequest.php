<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class TrackLearningAssetProgressRequest extends FormRequest
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
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100', 'required_without_all:completed,state'],
            'completed' => ['nullable', 'boolean', 'required_without_all:progress_percent,state'],
            'state' => ['nullable', 'array', 'required_without_all:progress_percent,completed'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'progress_percent' => [
                'description' => 'Current progress percent for the learning asset.',
                'example' => 40,
            ],
            'completed' => [
                'description' => 'Mark the learning asset as completed.',
                'example' => false,
            ],
            'state' => [
                'description' => 'Optional resume state such as current flashcard index or interactive step.',
                'example' => ['current_card_index' => 4],
            ],
        ];
    }
}
