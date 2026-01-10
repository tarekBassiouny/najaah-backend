<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\ErrorCodes;

class PublishBlockedException extends DomainException
{
    public function __construct(string $message = 'Publishing is blocked.', int $statusCode = 422)
    {
        parent::__construct($message, ErrorCodes::COURSE_PUBLISH_BLOCKED, $statusCode);
    }
}
