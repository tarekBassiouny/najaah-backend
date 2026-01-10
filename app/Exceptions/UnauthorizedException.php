<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\ErrorCodes;

class UnauthorizedException extends DomainException
{
    public function __construct(string $message = 'Authentication required.', int $statusCode = 401)
    {
        parent::__construct($message, ErrorCodes::UNAUTHORIZED, $statusCode);
    }
}
