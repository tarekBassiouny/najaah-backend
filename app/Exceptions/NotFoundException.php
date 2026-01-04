<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\ErrorCodes;

class NotFoundException extends DomainException
{
    public function __construct(string $message = 'Resource not found.', int $statusCode = 404)
    {
        parent::__construct($message, ErrorCodes::NOT_FOUND, $statusCode);
    }
}
