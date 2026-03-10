<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\LandingPages\Contracts\SubdomainResolverServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveCenterSubdomain
{
    public function __construct(
        private readonly SubdomainResolverServiceInterface $subdomainResolverService,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $subdomain = $this->subdomainResolverService->resolveSubdomain($request->getHost());

        if ($subdomain !== null) {
            $center = $this->subdomainResolverService->getCenterBySubdomain($subdomain);

            if ($center !== null) {
                $request->attributes->set('subdomain', $subdomain);
                $request->attributes->set('center', $center);
            }
        }

        $request->attributes->set(
            'is_system_domain',
            $this->subdomainResolverService->isSystemDomain($request->getHost())
        );

        return $next($request);
    }
}
