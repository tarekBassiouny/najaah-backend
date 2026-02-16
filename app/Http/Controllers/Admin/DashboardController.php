<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Filters\Admin\DashboardFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Dashboard\DashboardRequest;
use App\Http\Resources\Admin\DashboardResource;
use App\Models\Center;
use App\Models\User;
use App\Services\Dashboard\Contracts\DashboardServiceInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardServiceInterface $dashboardService
    ) {}

    /**
     * System dashboard endpoint.
     */
    public function index(DashboardRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        $payload = $this->dashboardService->get($admin, $request->filters());

        return response()->json([
            'success' => true,
            'data' => new DashboardResource($payload),
        ]);
    }

    /**
     * Center dashboard endpoint.
     */
    public function centerIndex(DashboardRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();
        $payload = $this->dashboardService->get($admin, new DashboardFilters(
            centerId: (int) $center->id
        ));

        return response()->json([
            'success' => true,
            'data' => new DashboardResource($payload),
        ]);
    }

    private function requireAdmin(): User
    {
        $admin = request()->user();

        if (! $admin instanceof User) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401));
        }

        return $admin;
    }
}
