<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Center;
use App\Models\User;
use App\Services\Timezone\Contracts\TimezoneServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to resolve and set timezone in request context.
 *
 * Resolution order:
 * 1. Explicit timezone query parameter (for analytics)
 * 2. From authenticated student's center
 * 3. From route-bound center (admin APIs)
 * 4. System default timezone
 */
class ResolveTimezone
{
    public function __construct(
        private readonly TimezoneServiceInterface $timezoneService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $timezone = $this->resolveTimezone($request);
        $request->attributes->set('timezone', $timezone);

        return $next($request);
    }

    private function resolveTimezone(Request $request): string
    {
        // 1. Explicit timezone param (for analytics or override)
        $explicitTimezone = $request->input('timezone');
        if (is_string($explicitTimezone) && $this->timezoneService->isValidTimezone($explicitTimezone)) {
            return $explicitTimezone;
        }

        // 2. From authenticated student's center
        $student = $request->user('student');
        if ($student instanceof User && $student->is_student) {
            $center = $student->center;
            if ($center instanceof Center) {
                return $this->timezoneService->getCenterTimezone($center);
            }
        }

        // 3. From route-bound center (admin APIs)
        $routeCenter = $request->route('center');
        if ($routeCenter instanceof Center) {
            return $this->timezoneService->getCenterTimezone($routeCenter);
        }

        // Check if center_id is provided in route
        $centerId = $request->route('center');
        if (is_numeric($centerId)) {
            $center = Center::find((int) $centerId);
            if ($center instanceof Center) {
                return $this->timezoneService->getCenterTimezone($center);
            }
        }

        // 4. From authenticated admin's center (if single center)
        $admin = $request->user('sanctum');
        if ($admin instanceof User && ! $admin->is_student && $admin->center_id !== null) {
            $center = $admin->center;
            if ($center instanceof Center) {
                return $this->timezoneService->getCenterTimezone($center);
            }
        }

        // 5. System default
        return $this->timezoneService->getSystemTimezone();
    }
}
