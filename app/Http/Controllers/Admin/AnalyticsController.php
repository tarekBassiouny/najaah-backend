<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Analytics\AnalyticsRequest;
use App\Http\Requests\Admin\Analytics\StudentAnalyticsRequest;
use App\Http\Resources\Admin\Analytics\AnalyticsCoursesMediaResource;
use App\Http\Resources\Admin\Analytics\AnalyticsDevicesRequestsResource;
use App\Http\Resources\Admin\Analytics\AnalyticsLearnersEnrollmentsResource;
use App\Http\Resources\Admin\Analytics\AnalyticsOverviewResource;
use App\Http\Resources\Admin\Analytics\AnalyticsStudentEngagementResource;
use App\Models\User;
use App\Services\Analytics\AnalyticsCoursesMediaService;
use App\Services\Analytics\AnalyticsDevicesRequestsService;
use App\Services\Analytics\AnalyticsLearnersEnrollmentsService;
use App\Services\Analytics\AnalyticsOverviewService;
use App\Services\Analytics\AnalyticsStudentEngagementService;
use App\Services\Centers\CenterScopeService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsOverviewService $overviewService,
        private readonly AnalyticsCoursesMediaService $coursesMediaService,
        private readonly AnalyticsLearnersEnrollmentsService $learnersEnrollmentsService,
        private readonly AnalyticsDevicesRequestsService $devicesRequestsService,
        private readonly AnalyticsStudentEngagementService $studentEngagementService,
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * Get analytics overview.
     */
    public function overview(AnalyticsRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        $payload = $this->overviewService->handle($admin, $request->filters());

        return response()->json([
            'success' => true,
            'data' => new AnalyticsOverviewResource($payload),
        ]);
    }

    /**
     * Get analytics for course media.
     */
    public function coursesMedia(AnalyticsRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        $payload = $this->coursesMediaService->handle($admin, $request->filters());

        return response()->json([
            'success' => true,
            'data' => new AnalyticsCoursesMediaResource($payload),
        ]);
    }

    /**
     * Get analytics for learner enrollments.
     */
    public function learnersEnrollments(AnalyticsRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        $payload = $this->learnersEnrollmentsService->handle($admin, $request->filters());

        return response()->json([
            'success' => true,
            'data' => new AnalyticsLearnersEnrollmentsResource($payload),
        ]);
    }

    /**
     * Get analytics for device requests.
     */
    public function devicesRequests(AnalyticsRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        $payload = $this->devicesRequestsService->handle($admin, $request->filters());

        return response()->json([
            'success' => true,
            'data' => new AnalyticsDevicesRequestsResource($payload),
        ]);
    }

    /**
     * Get analytics for a single student.
     */
    public function students(StudentAnalyticsRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        $filters = $request->filters();

        $student = User::query()
            ->where('is_student', true)
            ->where('id', $filters->studentId)
            ->first();

        if (! $student instanceof User) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Student not found.',
                ],
            ], 404));
        }

        $this->centerScopeService->assertAdminSameCenter($admin, $student);
        if ($filters->centerId !== null && (int) $filters->centerId !== (int) $student->center_id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CENTER_MISMATCH',
                    'message' => 'Student does not belong to this center.',
                ],
            ], 403));
        }

        $payload = $this->studentEngagementService->handle($admin, $student, $filters);

        return response()->json([
            'success' => true,
            'data' => new AnalyticsStudentEngagementResource($payload),
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
