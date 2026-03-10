<?php

declare(strict_types=1);

namespace App\Services\LandingPages\Contracts;

use App\Models\Center;

interface SubdomainResolverServiceInterface
{
    public function resolveSubdomain(?string $host = null): ?string;

    public function getCenterBySubdomain(string $subdomain): ?Center;

    public function isSystemDomain(?string $host = null): bool;

    public function buildCenterUrl(Center $center, string $path = ''): string;
}
