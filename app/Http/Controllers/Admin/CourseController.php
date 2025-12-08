<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Courses\AddSectionToCourseAction;
use App\Actions\Courses\AssignPdfToCourseAction;
use App\Actions\Courses\AssignVideoToCourseAction;
use App\Actions\Courses\CloneCourseAction;
use App\Actions\Courses\CreateCourseAction;
use App\Actions\Courses\DeleteCourseAction;
use App\Actions\Courses\ListCoursesAction;
use App\Actions\Courses\PublishCourseAction;
use App\Actions\Courses\RemovePdfFromCourseAction;
use App\Actions\Courses\RemoveVideoFromCourseAction;
use App\Actions\Courses\ReorderSectionsAction;
use App\Actions\Courses\ShowCourseAction;
use App\Actions\Courses\ToggleSectionVisibilityAction;
use App\Actions\Courses\UpdateCourseAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Courses\AddSectionRequest;
use App\Http\Requests\Courses\AssignPdfRequest;
use App\Http\Requests\Courses\AssignVideoRequest;
use App\Http\Requests\Courses\CloneCourseRequest;
use App\Http\Requests\Courses\CreateCourseRequest;
use App\Http\Requests\Courses\ReorderSectionsRequest;
use App\Http\Requests\Courses\UpdateCourseRequest;
use App\Http\Resources\Courses\CourseResource;
use App\Http\Resources\Courses\CourseSummaryResource;
use App\Http\Resources\Sections\SectionResource;
use App\Models\Course;
use App\Models\Section;
use Illuminate\Http\JsonResponse;

class CourseController extends Controller
{
    public function index(
        ListCoursesAction $listCoursesAction
    ): JsonResponse {
        $perPage = (int) request()->query('per_page', 15);
        $paginator = $listCoursesAction->execute($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Courses retrieved successfully',
            'data' => CourseSummaryResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(
        CreateCourseRequest $request,
        CreateCourseAction $createCourseAction
    ): JsonResponse {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $course = $createCourseAction->execute($data);

        return response()->json([
            'success' => true,
            'message' => 'Course created successfully',
            'data' => new CourseResource($course),
        ], 201);
    }

    public function show(
        Course $course,
        ShowCourseAction $showCourseAction
    ): JsonResponse {
        $course = $showCourseAction->execute((int) $course->id);

        return response()->json([
            'success' => true,
            'message' => 'Course retrieved successfully',
            'data' => $course !== null ? new CourseResource($course) : null,
        ]);
    }

    public function update(
        UpdateCourseRequest $request,
        Course $course,
        UpdateCourseAction $updateCourseAction
    ): JsonResponse {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $updated = $updateCourseAction->execute($course, $data);

        return response()->json([
            'success' => true,
            'message' => 'Course updated successfully',
            'data' => new CourseResource($updated),
        ]);
    }

    public function destroy(
        Course $course,
        DeleteCourseAction $deleteCourseAction
    ): JsonResponse {
        $deleteCourseAction->execute($course);

        return response()->json([
            'success' => true,
            'message' => 'Course deleted successfully',
            'data' => null,
        ], 204);
    }

    public function addSection(
        AddSectionRequest $request,
        Course $course,
        AddSectionToCourseAction $addSectionToCourseAction
    ): JsonResponse {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $section = $addSectionToCourseAction->execute($course, $data);

        return response()->json([
            'success' => true,
            'message' => 'Section added successfully',
            'data' => new SectionResource($section),
        ]);
    }

    public function reorderSections(
        ReorderSectionsRequest $request,
        Course $course,
        ReorderSectionsAction $reorderSectionsAction
    ): JsonResponse {
        /** @var array{sections: array<int, int>} $data */
        $data = $request->validated();
        $reorderSectionsAction->execute($course, $data['sections']);

        return response()->json([
            'success' => true,
            'message' => 'Sections reordered successfully',
            'data' => null,
        ]);
    }

    public function toggleSectionVisibility(
        Course $course,
        Section $section,
        ToggleSectionVisibilityAction $toggleSectionVisibilityAction
    ): JsonResponse {
        $updated = $toggleSectionVisibilityAction->execute($section);

        return response()->json([
            'success' => true,
            'message' => 'Section visibility updated successfully',
            'data' => new SectionResource($updated),
        ]);
    }

    public function assignVideo(
        AssignVideoRequest $request,
        Course $course,
        AssignVideoToCourseAction $assignVideoToCourseAction
    ): JsonResponse {
        /** @var array{video_id:int,order_index?:int|null} $data */
        $data = $request->validated();
        $assignVideoToCourseAction->execute($course, (int) $data['video_id']);

        return response()->json([
            'success' => true,
            'message' => 'Video assigned successfully',
            'data' => null,
        ], 201);
    }

    public function removeVideo(
        Course $course,
        int $video,
        RemoveVideoFromCourseAction $removeVideoFromCourseAction
    ): JsonResponse {
        $removeVideoFromCourseAction->execute($course, $video);

        return response()->json([
            'success' => true,
            'message' => 'Video removed successfully',
            'data' => null,
        ]);
    }

    public function assignPdf(
        AssignPdfRequest $request,
        Course $course,
        AssignPdfToCourseAction $assignPdfToCourseAction
    ): JsonResponse {
        /** @var array{pdf_id:int,order_index?:int|null} $data */
        $data = $request->validated();
        $assignPdfToCourseAction->execute($course, (int) $data['pdf_id']);

        return response()->json([
            'success' => true,
            'message' => 'PDF assigned successfully',
            'data' => null,
        ], 201);
    }

    public function removePdf(
        Course $course,
        int $pdf,
        RemovePdfFromCourseAction $removePdfFromCourseAction
    ): JsonResponse {
        $removePdfFromCourseAction->execute($course, $pdf);

        return response()->json([
            'success' => true,
            'message' => 'PDF removed successfully',
            'data' => null,
        ]);
    }

    public function publish(
        Course $course,
        PublishCourseAction $publishCourseAction
    ): JsonResponse {
        $published = $publishCourseAction->execute($course);

        return response()->json([
            'success' => true,
            'message' => 'Course published successfully',
            'data' => new CourseResource($published),
        ]);
    }

    public function cloneCourse(
        CloneCourseRequest $request,
        Course $course,
        CloneCourseAction $cloneCourseAction
    ): JsonResponse {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $options = $data['options'] ?? [];

        if (! is_array($options)) {
            $options = [];
        }

        $cloned = $cloneCourseAction->execute($course, $options);

        return response()->json([
            'success' => true,
            'message' => 'Course cloned successfully',
            'data' => new CourseResource($cloned),
        ], 201);
    }
}
