<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\VideoAccess;

use Illuminate\Foundation\Http\FormRequest;

class ExportVideoCodeBatchRequest extends FormRequest
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
            'start_sequence' => ['sometimes', 'integer', 'min:1'],
            'end_sequence' => ['sometimes', 'integer', 'min:1'],
            'cards_per_page' => ['sometimes', 'integer', 'in:4,6,8'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function queryParameters(): array
    {
        return [
            'start_sequence' => [
                'description' => 'Starting sequence number for export range.',
                'example' => 1,
            ],
            'end_sequence' => [
                'description' => 'Ending sequence number for export range.',
                'example' => 100,
            ],
            'cards_per_page' => [
                'description' => 'Number of code cards per page for PDF export (4, 6, or 8).',
                'example' => 8,
            ],
        ];
    }
}
