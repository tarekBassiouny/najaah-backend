<?php

declare(strict_types=1);

namespace App\Services\LandingPages;

use App\Models\Center;
use App\Services\LandingPages\Contracts\SubdomainResolverServiceInterface;
use Illuminate\Support\Facades\Config;

class SubdomainResolverService implements SubdomainResolverServiceInterface
{
    public function resolveSubdomain(?string $host = null): ?string
    {
        $host = $host ?? request()->getHost();
        $baseDomain = $this->getBaseDomain();

        if ($host === $baseDomain) {
            return null;
        }

        $suffix = '.'.$baseDomain;
        if (! str_ends_with($host, $suffix)) {
            return null;
        }

        $subdomain = substr($host, 0, -strlen($suffix));

        if ($subdomain === '' || $subdomain === 'www') {
            return null;
        }

        return $subdomain;
    }

    public function getCenterBySubdomain(string $subdomain): ?Center
    {
        return Center::where('slug', $subdomain)->first();
    }

    public function isSystemDomain(?string $host = null): bool
    {
        $host = $host ?? request()->getHost();
        $baseDomain = $this->getBaseDomain();

        return $host === $baseDomain || $host === 'www.'.$baseDomain;
    }

    public function buildCenterUrl(Center $center, string $path = ''): string
    {
        $frontendUrl = (string) Config::get('app.frontend_url', '');
        $parsedFrontendUrl = parse_url($frontendUrl);

        $scheme = $parsedFrontendUrl['scheme'] ?? Config::get('app.url_scheme', 'https');
        $host = $parsedFrontendUrl['host'] ?? $this->getBaseDomain();
        $port = isset($parsedFrontendUrl['port']) ? ':'.$parsedFrontendUrl['port'] : '';

        $url = sprintf('%s://%s.%s%s', $scheme, $center->slug, $host, $port);

        if ($path !== '') {
            $url .= '/'.ltrim($path, '/');
        }

        return $url;
    }

    private function getBaseDomain(): string
    {
        return Config::get('app.base_domain', 'najaah.me');
    }
}
