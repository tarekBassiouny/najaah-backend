<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Courses\AddSectionToCourseAction;
use App\Actions\Courses\AssignPdfToCourseAction;
use App\Actions\Courses\AssignVideoToCourseAction;
use App\Actions\Courses\CloneCourseAction;
use App\Actions\Courses\CreateCourseAction;
use App\Actions\Courses\DeleteCourseAction;
use App\Actions\Courses\PublishCourseAction;
use App\Actions\Courses\RemovePdfFromCourseAction;
use App\Actions\Courses\RemoveVideoFromCourseAction;
use App\Actions\Courses\ReorderSectionsAction;
use App\Actions\Courses\ShowCourseAction;
use App\Actions\Courses\ToggleSectionVisibilityAction;
use App\Actions\Courses\UpdateCourseAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListCoursesRequest;
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
use App\Models\User;
use App\Services\Admin\CourseQueryService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class CourseController extends Controller
{
    public function index(
        ListCoursesRequest $request,
        CourseQueryService $queryService
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $perPage = (int) $request->integer('per_page', 15);
        /** @var array<string, mixed> $filters */
        $filters = $request->validated();
        $paginator = $queryService->build($admin, $filters)->paginate($perPage);

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
        $admin = $this->requireAdmin();
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $course = $createCourseAction->execute($admin, $data);

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
        $admin = $this->requireAdmin();
        $course = $showCourseAction->execute($admin, (int) $course->id);

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
        $admin = $this->requireAdmin();
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $updated = $updateCourseAction->execute($admin, $course, $data);

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
        $admin = $this->requireAdmin();
        $deleteCourseAction->execute($admin, $course);

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
        $admin = $this->requireAdmin();
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $section = $addSectionToCourseAction->execute($admin, $course, $data);

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
        $admin = $this->requireAdmin();
        /** @var array{sections: array<int, int>} $data */
        $data = $request->validated();
        $reorderSectionsAction->execute($admin, $course, $data['sections']);

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
        $admin = $this->requireAdmin();
        $updated = $toggleSectionVisibilityAction->execute($admin, $section);

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
        $admin = $this->requireAdmin();
        /** @var array{video_id:int,order_index?:int|null} $data */
        $data = $request->validated();
        $assignVideoToCourseAction->execute($admin, $course, (int) $data['video_id']);

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
        $admin = $this->requireAdmin();
        $removeVideoFromCourseAction->execute($admin, $course, $video);

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
        $admin = $this->requireAdmin();
        /** @var array{pdf_id:int,order_index?:int|null} $data */
        $data = $request->validated();
        $assignPdfToCourseAction->execute($admin, $course, (int) $data['pdf_id']);

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
        $admin = $this->requireAdmin();
        $removePdfFromCourseAction->execute($admin, $course, $pdf);

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
        $admin = $this->requireAdmin();
        $published = $publishCourseAction->execute($admin, $course);

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
        $admin = $this->requireAdmin();
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $options = $data['options'] ?? [];

        if (! is_array($options)) {
            $options = [];
        }

        $cloned = $cloneCourseAction->execute($admin, $course, $options);

        return response()->json([
            'success' => true,
            'message' => 'Course cloned successfully',
            'data' => new CourseResource($cloned),
        ], 201);
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
