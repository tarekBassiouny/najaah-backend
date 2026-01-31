<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Analytics\AnalyticsRequest;
use App\Http\Resources\Admin\Analytics\AnalyticsCoursesMediaResource;
use App\Http\Resources\Admin\Analytics\AnalyticsDevicesRequestsResource;
use App\Http\Resources\Admin\Analytics\AnalyticsLearnersEnrollmentsResource;
use App\Http\Resources\Admin\Analytics\AnalyticsOverviewResource;
use App\Models\User;
use App\Services\Analytics\AnalyticsCoursesMediaService;
use App\Services\Analytics\AnalyticsDevicesRequestsService;
use App\Services\Analytics\AnalyticsLearnersEnrollmentsService;
use App\Services\Analytics\AnalyticsOverviewService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsOverviewService $overviewService,
        private readonly AnalyticsCoursesMediaService $coursesMediaService,
        private readonly AnalyticsLearnersEnrollmentsService $learnersEnrollmentsService,
        private readonly AnalyticsDevicesRequestsService $devicesRequestsService
    ) {}

    public function overview(AnalyticsRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        $payload = $this->overviewService->handle($admin, $request->filters());

        return response()->json([
            'success' => true,
            'data' => new AnalyticsOverviewResource($payload),
        ]);
    }

    public function coursesMedia(AnalyticsRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        $payload = $this->coursesMediaService->handle($admin, $request->filters());

        return response()->json([
            'success' => true,
            'data' => new AnalyticsCoursesMediaResource($payload),
        ]);
    }

    public function learnersEnrollments(AnalyticsRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        $payload = $this->learnersEnrollmentsService->handle($admin, $request->filters());

        return response()->json([
            'success' => true,
            'data' => new AnalyticsLearnersEnrollmentsResource($payload),
        ]);
    }

    public function devicesRequests(AnalyticsRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        $payload = $this->devicesRequestsService->handle($admin, $request->filters());

        return response()->json([
            'success' => true,
            'data' => new AnalyticsDevicesRequestsResource($payload),
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
