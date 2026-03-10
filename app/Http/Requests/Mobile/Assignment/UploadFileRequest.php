<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile\Assignment;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->is_student === true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:102400'], // 100MB max, specific validation in controller
        ];
    }
}
