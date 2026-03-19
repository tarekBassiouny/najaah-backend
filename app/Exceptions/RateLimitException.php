<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class RateLimitException extends RuntimeException
{
    public function __construct(
        public readonly string $provider,
        string $message = 'AI provider rate limit exceeded.'
    ) {
        parent::__construct($message);
    }
}
