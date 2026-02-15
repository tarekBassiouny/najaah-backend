<?php

namespace App\Http\Middleware;

use App\Exceptions\CenterMismatchException;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class JwtAdminMiddleware
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        Auth::shouldUse('admin');

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('admin');
        $user = $guard->user() ?? $guard->authenticate();

        if (! $user instanceof User) {
            return response()->json(['success' => false, 'error' => 'Admin not found'], 401);
        }

        // must not allow students
        if ($user->is_student) {
            return response()->json(['success' => false, 'error' => 'Not an admin'], 401);
        }

        $resolvedCenterId = is_numeric($request->attributes->get('resolved_center_id'))
            ? (int) $request->attributes->get('resolved_center_id')
            : null;

        try {
            $this->centerScopeService->assertResolvedApiCenterScope($user, $resolvedCenterId);
        } catch (CenterMismatchException) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CENTER_MISMATCH',
                    'message' => 'Center mismatch.',
                ],
            ], 403);
        }

        $request->setUserResolver(fn (): \App\Models\User => $user);

        return $next($request);
    }
}
