<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile;

use App\Http\Requests\Mobile\Concerns\ValidatesSessionOwnership;
use Illuminate\Foundation\Http\FormRequest;

class CloseSessionRequest extends FormRequest
{
    use ValidatesSessionOwnership;
}
