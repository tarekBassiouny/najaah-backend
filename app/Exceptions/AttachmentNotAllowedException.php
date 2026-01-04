<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\ErrorCodes;

class AttachmentNotAllowedException extends DomainException
{
    public function __construct(string $message = 'Attachment is not allowed.', int $statusCode = 422)
    {
        parent::__construct($message, ErrorCodes::ATTACHMENT_NOT_ALLOWED, $statusCode);
    }
}
