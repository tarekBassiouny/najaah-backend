<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Courses\AssignInstructorRequest;
use App\Http\Resources\Admin\Courses\CourseResource;
use App\Models\Center;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\User;
use App\Services\Courses\Contracts\CourseInstructorServiceInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class CourseInstructorController extends Controller
{
    public function __construct(
        private readonly CourseInstructorServiceInterface $courseInstructorService
    ) {}

    /**
     * Assign an instructor to a course.
     */
    public function store(
        AssignInstructorRequest $request,
        Center $center,
        Course $course
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);

        /** @var array{instructor_id:int,role?:string|null} $data */
        $data = $request->validated();
        $instructor = Instructor::query()->findOrFail((int) $data['instructor_id']);
        $this->assertInstructorBelongsToCenter($center, $instructor);

        $this->courseInstructorService->assign($course, $instructor, $data['role'] ?? null, $admin);
        $updated = $course->load(['instructors', 'primaryInstructor']);

        return response()->json([
            'success' => true,
            'message' => 'Instructor assigned successfully',
            'data' => new CourseResource($updated),
        ], 201);
    }

    /**
     * Remove an instructor from a course.
     */
    public function destroy(
        Center $center,
        Course $course,
        Instructor $instructor
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $this->assertInstructorBelongsToCenter($center, $instructor);

        $this->courseInstructorService->remove($course, $instructor, $admin);
        $updated = $course->load(['instructors', 'primaryInstructor']);

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

    private function assertCourseBelongsToCenter(Center $center, Course $course): void
    {
        if ((int) $course->center_id !== (int) $center->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Course not found.',
                ],
            ], 404));
        }
    }

    private function assertInstructorBelongsToCenter(Center $center, Instructor $instructor): void
    {
        if ((int) $instructor->center_id !== (int) $center->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Instructor not found.',
                ],
            ], 404));
        }
    }
}
