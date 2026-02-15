<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Centers\Contracts\CenterScopeServiceInterface;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemAdminScope
{
    public function __construct(
        private readonly CenterScopeServiceInterface $centerScopeService
    ) {}

    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        $resolvedCenterId = $this->resolveCenterId($request->attributes->get('resolved_center_id'));
        if ($resolvedCenterId !== null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CENTER_MISMATCH',
                    'message' => 'System scope access requires a system API key.',
                ],
            ], 403);
        }

        if ($user->is_student || ! $this->centerScopeService->isSystemSuperAdmin($user)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'PERMISSION_DENIED',
                    'message' => 'System scope access is required.',
                ],
            ], 403);
        }

        return $next($request);
    }

    private function resolveCenterId(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
