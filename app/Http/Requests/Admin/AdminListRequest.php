<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

abstract class AdminListRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>|string>
     */
    protected function listRules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * Normalize query-string boolean fields (e.g. "true"/"false") before validation.
     *
     * @param  array<int, string>  $fields
     */
    protected function normalizeBooleanFields(array $fields): void
    {
        $data = $this->all();

        foreach ($fields as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $normalized = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($normalized === null) {
                continue;
            }

            $data[$field] = $normalized;
        }

        $this->replace($data);
    }
}
