<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Courses\AssignInstructorToCourseAction;
use App\Actions\Courses\RemoveInstructorFromCourseAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Course\AssignInstructorRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\Instructor;
use Illuminate\Http\JsonResponse;

class CourseInstructorController extends Controller
{
    public function __construct(
        private readonly AssignInstructorToCourseAction $assignAction,
        private readonly RemoveInstructorFromCourseAction $removeAction
    ) {}

    public function store(AssignInstructorRequest $request, Course $course): JsonResponse
    {
        $data = $request->validated();
        $instructorId = is_numeric($data['instructor_id'] ?? null) ? (int) $data['instructor_id'] : 0;
        $role = is_string($data['role'] ?? null) ? $data['role'] : null;
        $instructor = Instructor::findOrFail($instructorId);

        $course = $this->assignAction->execute($course, $instructor, $role);

        return response()->json([
            'success' => true,
            'message' => 'Instructor assigned successfully',
            'data' => new CourseResource($course),
        ], 201);
    }

    public function destroy(Course $course, Instructor $instructor): JsonResponse
    {
        $course = $this->removeAction->execute($course, $instructor);

        return response()->json([
            'success' => true,
            'message' => 'Instructor removed successfully',
            'data' => new CourseResource($course),
        ]);
    }
}
