<?php

declare(strict_types=1);

namespace App\Services\VideoAccess\Data;

use App\Models\VideoAccessCode;
use App\Models\VideoAccessRequest;

class ApprovalResult
{
    public function __construct(
        public readonly VideoAccessRequest $request,
        public readonly VideoAccessCode $generatedCode,
        public readonly bool $whatsAppSent,
        public readonly ?string $whatsAppError
    ) {}
}
