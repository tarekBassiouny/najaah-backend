<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Enrollments\Contracts\EnrollmentServiceInterface;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveEnrollment
{
    public function __construct(private readonly EnrollmentServiceInterface $enrollmentService) {}

    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $user = $request->user();
        $course = $request->route('course');

        if (! $user instanceof User || $user->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access this resource.',
                ],
            ], 403);
        }

        if (! $course instanceof Course) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_COURSE',
                    'message' => 'Course context is required.',
                ],
            ], 400);
        }

        $enrollment = $this->enrollmentService->getActiveEnrollment($user, $course);

        if (! $enrollment instanceof Enrollment) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ENROLLMENT_REQUIRED',
                    'message' => 'Active enrollment is required to access this course.',
                ],
            ], 403);
        }

        $request->attributes->set('enrollment', $enrollment);

        return $next($request);
    }
}
