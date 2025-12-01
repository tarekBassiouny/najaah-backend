<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\UserNotDefinedException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $error = null;

        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (! $user) {
                $error = 'User not found';
            }
        } catch (TokenExpiredException) {
            $error = 'Token expired';
        } catch (UserNotDefinedException) {
            $error = 'User not defined for this token';
        } catch (JWTException) {
            $error = 'Token invalid or not provided';
        }

        if ($error !== null) {
            return response()->json([
                'error' => $error,
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
