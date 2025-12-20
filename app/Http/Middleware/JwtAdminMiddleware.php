<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class JwtAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        Auth::shouldUse('admin');

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = Auth::guard('admin');
        $user = $guard->user() ?? $guard->authenticate();

        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Admin not found'], 401);
        }

        // must not allow students
        if ($user instanceof \App\Models\User && $user->is_student) {
            return response()->json(['success' => false, 'error' => 'Not an admin'], 401);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
