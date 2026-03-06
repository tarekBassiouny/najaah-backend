<?php

declare(strict_types=1);

namespace App\Enums;

enum SchoolType: int
{
    case Public = 0;
    case Private = 1;
    case International = 2;
    case Other = 3;

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Public',
            self::Private => 'Private',
            self::International => 'International',
            self::Other => 'Other',
        };
    }
}
