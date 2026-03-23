<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ParentStudentLink;
use Illuminate\Foundation\Events\Dispatchable;

class ParentLinkApproved
{
    use Dispatchable;

    public function __construct(
        public readonly ParentStudentLink $link
    ) {}
}
