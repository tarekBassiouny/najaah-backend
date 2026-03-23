<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Parents;

use Illuminate\Foundation\Http\FormRequest;

class AdminCreateLinkRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'parent_user_id' => ['required', 'integer', 'exists:users,id'],
            'student_user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
