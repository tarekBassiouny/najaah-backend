<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Translation extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'translatable_type',
        'translatable_id',
        'field',
        'locale',
        'value',
    ];

    public function setLocaleAttribute(?string $locale): void
    {
        if ($locale === null) {
            $this->attributes['locale'] = null;

            return;
        }

        $normalized = strtolower(trim($locale));
        $normalized = str_replace('_', '-', $normalized);
        $this->attributes['locale'] = $normalized === '' ? null : substr($normalized, 0, 5);
    }

    public function setValueAttribute(mixed $value): void
    {
        if (is_array($value) || is_object($value)) {
            throw new InvalidArgumentException('Translation value must be a scalar string.');
        }

        $this->attributes['value'] = $value === null ? '' : (string) $value;
    }
}
