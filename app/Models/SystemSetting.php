<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $key
 * @property array<mixed>|null $value
 * @property bool $is_public
 */
class SystemSetting extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'key',
        'value',
        'is_public',
    ];

    protected $casts = [
        'value' => 'array',
        'is_public' => 'boolean',
    ];
}
