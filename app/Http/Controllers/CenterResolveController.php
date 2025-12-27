<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\resolvedCenterResource;
use App\Models\Center;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CenterResolveController extends Controller
{
    public function show(Request $request, string $slug): JsonResponse
    {
        $center = Center::query()
            ->with('setting')
            ->where('slug', $slug)
            ->first();

        if (! $center instanceof Center) {
            return $this->deny('NOT_FOUND', 'Center not found.', 404);
        }

        $resolvedCenterId = $request->attributes->get('resolved_center_id');
        if (! is_numeric($resolvedCenterId) || (int) $resolvedCenterId !== (int) $center->id) {
            return $this->deny('CENTER_MISMATCH', 'API key does not match center.', 403);
        }

        if ($center->onboarding_status !== Center::ONBOARDING_ACTIVE) {
            return $this->deny('CENTER_INACTIVE', 'Center is not active.', 403);
        }

        return response()->json([
            'success' => true,
            'data' => new resolvedCenterResource($center),
        ]);
    }

    private function deny(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $status);
    }
}
