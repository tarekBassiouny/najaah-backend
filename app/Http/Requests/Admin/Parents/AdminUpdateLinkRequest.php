<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Parents;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateLinkRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:approve,reject,revoke'],
        ];
    }
}
