<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Courses\ShowCourseAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Courses\CourseResource;
use App\Http\Resources\Courses\CourseSummaryResource;
use App\Http\Resources\PdfResource;
use App\Http\Resources\VideoResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Pdf;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * @queryParam per_page int Items per page. Example: 15
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = (int) $request->query('per_page', 15);

        if (! ($user instanceof User) || $user->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access courses.',
                ],
            ], 403);
        }

        $paginator = Course::query()
            ->where('status', 3)
            ->whereIn('id', function ($query) use ($user): void {
                $query->select('course_id')
                    ->from('enrollments')
                    ->whereNull('deleted_at')
                    ->where('status', Enrollment::STATUS_ACTIVE)
                    ->where('user_id', $user->id);
            })
            ->paginate($perPage);

        $collection = collect($paginator->items());

        return response()->json([
            'success' => true,
            'message' => 'Courses retrieved successfully',
            'data' => CourseSummaryResource::collection($collection),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Course $course, ShowCourseAction $showCourseAction): JsonResponse
    {
        $course = $showCourseAction->execute((int) $course->id);

        if ($course === null || (int) $course->status !== 3) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Course not found',
                ],
            ], 404);
        }

        $course->load(['sections.videos', 'sections.pdfs']);

        return response()->json([
            'success' => true,
            'message' => 'Course retrieved successfully',
            'data' => new CourseResource($course),
        ]);
    }

    public function listVideos(Course $course): JsonResponse
    {
        if ((int) $course->status !== 3) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Course not found',
                ],
            ], 404);
        }

        $videos = $course->videos()->get();

        return response()->json([
            'success' => true,
            'message' => 'Course videos retrieved successfully',
            'data' => VideoResource::collection($videos),
        ]);
    }

    public function showVideo(Course $course, Video $video): JsonResponse
    {
        if ((int) $course->status !== 3) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Course not found',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Video retrieved successfully',
            'data' => new VideoResource($video),
        ]);
    }

    public function listPdfs(Course $course): JsonResponse
    {
        if ((int) $course->status !== 3) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Course not found',
                ],
            ], 404);
        }

        $pdfs = $course->pdfs()->get();

        return response()->json([
            'success' => true,
            'message' => 'Course PDFs retrieved successfully',
            'data' => PdfResource::collection($pdfs),
        ]);
    }

    public function showPdf(Course $course, Pdf $pdf): JsonResponse
    {
        if ((int) $course->status !== 3) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Course not found',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'PDF retrieved successfully',
            'data' => new PdfResource($pdf),
        ]);
    }
}
