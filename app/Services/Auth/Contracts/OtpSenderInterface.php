<?php

declare(strict_types=1);

namespace App\Services\Auth\Contracts;

interface OtpSenderInterface
{
    public function send(string $destination, string $otp): void;

    public function provider(): string;
}
