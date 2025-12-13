<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class JwtMobileMiddleware
{
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        Auth::shouldUse('api');

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('api');

        try {
            $user = $guard->user() ?? $guard->authenticate();
        } catch (\Throwable) {
            return response()->json(['success' => false, 'error' => 'User not found'], 401);
        }

        if (! $user) {
            return response()->json(['success' => false, 'error' => 'User not found'], 401);
        }

        // Set resolved user for controllers
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
