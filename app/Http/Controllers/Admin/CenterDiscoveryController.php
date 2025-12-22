<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\CenterDiscoveryResource;
use App\Models\Center;
use Illuminate\Http\JsonResponse;

class CenterDiscoveryController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $center = Center::query()
            ->with('setting')
            ->where('slug', $slug)
            ->first();

        if ($center === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Center not found.',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CenterDiscoveryResource($center),
        ]);
    }
}
