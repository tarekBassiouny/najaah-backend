<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\StudentProfileResource;
use App\Models\Center;
use App\Models\Pivots\UserCenter;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use App\Services\Students\StudentProfileQueryService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StudentProfileController extends Controller
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly StudentProfileQueryService $studentProfileQueryService
    ) {}

    /**
     * Cache TTL in seconds (5 minutes).
     * Watch data can change during playback, so we use a moderate TTL.
     */
    private const CACHE_TTL_SECONDS = 300;

    /**
     * Display the specified student profile with courses and videos.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        return $this->showStudentProfile($request, $user, null);
    }

    /**
     * Display the specified center student profile.
     */
    public function centerShow(Request $request, Center $center, User $user): JsonResponse
    {
        if (! $this->studentBelongsToCenter($user, $center)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Student not found.',
                ],
            ], 404));
        }

        return $this->showStudentProfile($request, $user, $center);
    }

    private function showStudentProfile(Request $request, User $user, ?Center $center): JsonResponse
    {
        if (! $user->is_student) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_A_STUDENT',
                    'message' => 'The specified user is not a student.',
                ],
            ], 404);
        }

        $admin = $request->user();
        if (! $admin instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        if ($center instanceof Center) {
            $this->centerScopeService->assertAdminSameCenter($admin, $center);

            if (! $this->studentBelongsToCenter($user, $center)) {
                throw new HttpResponseException(response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Student not found.',
                    ],
                ], 404));
            }
        } else {
            $this->centerScopeService->assertAdminSameCenter($admin, $user);
        }

        $resolvedCenterId = $request->attributes->get('resolved_center_id');
        if (is_numeric($resolvedCenterId) && ! ($center instanceof Center)) {
            $this->studentProfileQueryService->assertMatchesResolvedCenterScope(
                $user,
                (int) $resolvedCenterId
            );
        }

        $cacheKey = $this->getCacheKey($user);

        /** @var array<string, mixed> $data */
        $data = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($user): array {
            return $this->buildProfileData($user);
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Build the student profile data.
     *
     * @return array<string, mixed>
     */
    private function buildProfileData(User $user): array
    {
        $this->studentProfileQueryService->load($user);

        return (new StudentProfileResource($user))->resolve();
    }

    /**
     * Generate cache key for student profile.
     */
    private function getCacheKey(User $user): string
    {
        return 'student_profile:'.$user->id;
    }

    /**
     * Invalidate cache for a specific student.
     */
    public static function invalidateCache(int $userId): void
    {
        Cache::forget('student_profile:'.$userId);
    }

    private function studentBelongsToCenter(User $student, Center $center): bool
    {
        return UserCenter::query()
            ->where('user_id', (int) $student->id)
            ->where('center_id', (int) $center->id)
            ->where('type', 'student')
            ->exists();
    }
}
