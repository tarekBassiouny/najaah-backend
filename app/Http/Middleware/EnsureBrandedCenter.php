<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\CenterType;
use App\Models\Center;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBrandedCenter
{
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $routeCenter = $request->route('center');
        $centerId = is_numeric($routeCenter) ? (int) $routeCenter : null;

        if ($centerId === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Center not found',
                ],
            ], 404);
        }

        $center = Center::query()->find($centerId);

        if (! $center instanceof Center) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Center not found',
                ],
            ], 404);
        }

        if ($center->type !== CenterType::Branded) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Landing pages are allowed only for branded centers.',
                ],
            ], 422);
        }

        return $next($request);
    }
}
