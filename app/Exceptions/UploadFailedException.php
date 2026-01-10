<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\ErrorCodes;

class UploadFailedException extends DomainException
{
    public function __construct(string $message = 'Upload failed.', int $statusCode = 422)
    {
        parent::__construct($message, ErrorCodes::UPLOAD_FAILED, $statusCode);
    }
}
