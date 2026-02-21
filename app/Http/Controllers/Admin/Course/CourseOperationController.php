<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Course;

use App\Actions\Admin\Courses\UploadCourseThumbnailAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Courses\AssignPdfRequest;
use App\Http\Requests\Admin\Courses\AssignVideoRequest;
use App\Http\Requests\Admin\Courses\CloneCourseRequest;
use App\Http\Requests\Admin\Courses\UploadCourseThumbnailRequest;
use App\Http\Resources\Admin\Courses\CourseResource;
use App\Models\Center;
use App\Models\Course;
use App\Models\User;
use App\Services\Courses\Contracts\CourseAttachmentServiceInterface;
use App\Services\Courses\Contracts\CourseWorkflowServiceInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class CourseOperationController extends Controller
{
    /**
     * Assign a video to a course.
     */
    public function assignVideo(
        AssignVideoRequest $request,
        Center $center,
        Course $course,
        CourseAttachmentServiceInterface $courseAttachmentService
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        /** @var array{video_id:int,order_index?:int|null} $data */
        $data = $request->validated();
        $courseAttachmentService->assignVideo($course, (int) $data['video_id'], $admin);

        return response()->json([
            'success' => true,
            'message' => 'Video assigned successfully',
            'data' => null,
        ], 201);
    }

    /**
     * Remove a video from a course.
     */
    public function removeVideo(
        Center $center,
        Course $course,
        int $video,
        CourseAttachmentServiceInterface $courseAttachmentService
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $courseAttachmentService->removeVideo($course, $video, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Video removed successfully',
            'data' => null,
        ]);
    }

    /**
     * Assign a PDF to a course.
     */
    public function assignPdf(
        AssignPdfRequest $request,
        Center $center,
        Course $course,
        CourseAttachmentServiceInterface $courseAttachmentService
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        /** @var array{pdf_id:int,order_index?:int|null} $data */
        $data = $request->validated();
        $courseAttachmentService->assignPdf($course, (int) $data['pdf_id'], $admin);

        return response()->json([
            'success' => true,
            'message' => 'PDF assigned successfully',
            'data' => null,
        ], 201);
    }

    /**
     * Remove a PDF from a course.
     */
    public function removePdf(
        Center $center,
        Course $course,
        int $pdf,
        CourseAttachmentServiceInterface $courseAttachmentService
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $courseAttachmentService->removePdf($course, $pdf, $admin);

        return response()->json([
            'success' => true,
            'message' => 'PDF removed successfully',
            'data' => null,
        ]);
    }

    /**
     * Publish a course.
     */
    public function publish(
        Center $center,
        Course $course,
        CourseWorkflowServiceInterface $courseWorkflowService
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        $published = $courseWorkflowService->publishCourse($course, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Course published successfully',
            'data' => new CourseResource($published),
        ]);
    }

    /**
     * Clone a course.
     */
    public function cloneCourse(
        CloneCourseRequest $request,
        Center $center,
        Course $course,
        CourseWorkflowServiceInterface $courseWorkflowService
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $options = $data['options'] ?? [];

        if (! is_array($options)) {
            $options = [];
        }

        $cloned = $courseWorkflowService->cloneCourse($course, $admin, $options);

        return response()->json([
            'success' => true,
            'message' => 'Course cloned successfully',
            'data' => new CourseResource($cloned),
        ], 201);
    }

    /**
     * Upload a course thumbnail.
     */
    public function uploadThumbnail(
        UploadCourseThumbnailRequest $request,
        Center $center,
        Course $course,
        UploadCourseThumbnailAction $action
    ): JsonResponse {
        $admin = $this->requireAdmin();
        $this->assertCourseBelongsToCenter($center, $course);

        /** @var \Illuminate\Http\UploadedFile $thumbnail */
        $thumbnail = $request->file('thumbnail');
        $updated = $action->execute($course, $thumbnail, $admin);

        return response()->json([
            'success' => true,
            'message' => 'Course thumbnail updated successfully',
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
}
