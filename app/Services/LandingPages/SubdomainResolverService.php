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

        $suffix = '.' . $baseDomain;
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

        return $host === $baseDomain || $host === 'www.' . $baseDomain;
    }

    public function buildCenterUrl(Center $center, string $path = ''): string
    {
        $baseDomain = $this->getBaseDomain();
        $scheme = Config::get('app.url_scheme', 'https');

        $url = sprintf('%s://%s.%s', $scheme, $center->slug, $baseDomain);

        if ($path !== '') {
            $url .= '/' . ltrim($path, '/');
        }

        return $url;
    }

    private function getBaseDomain(): string
    {
        return Config::get('app.base_domain', 'najaah.me');
    }
}
