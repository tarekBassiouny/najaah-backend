<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\ErrorCodes;

class CenterMismatchException extends DomainException
{
    public function __construct(string $message = 'Resource does not belong to your center.', int $statusCode = 403)
    {
        parent::__construct($message, ErrorCodes::CENTER_MISMATCH, $statusCode);
    }
}
