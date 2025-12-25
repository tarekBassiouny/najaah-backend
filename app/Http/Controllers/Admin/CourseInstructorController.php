<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Courses\AssignInstructorToCourseAction;
use App\Actions\Courses\RemoveInstructorFromCourseAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Courses\AssignInstructorRequest;
use App\Http\Resources\Courses\CourseResource;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class CourseInstructorController extends Controller
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    public function store(
        AssignInstructorRequest $request,
        Course $course,
        AssignInstructorToCourseAction $assignAction
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->centerScopeService->assertAdminSameCenter($admin, $course);

        /** @var array{instructor_id:int,role?:string|null} $data */
        $data = $request->validated();
        $instructor = Instructor::query()->findOrFail((int) $data['instructor_id']);
        $this->centerScopeService->assertAdminSameCenter($admin, $instructor);

        $updated = $assignAction->execute($course, $instructor, $data['role'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Instructor assigned successfully',
            'data' => new CourseResource($updated),
        ], 201);
    }

    public function destroy(
        Course $course,
        Instructor $instructor,
        RemoveInstructorFromCourseAction $removeAction
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->centerScopeService->assertAdminSameCenter($admin, $course);
        $this->centerScopeService->assertAdminSameCenter($admin, $instructor);

        $updated = $removeAction->execute($course, $instructor);

        return response()->json([
            'success' => true,
            'message' => 'Instructor removed successfully',
            'data' => new CourseResource($updated),
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
