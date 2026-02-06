<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\SettingsPreviewRequest;
use App\Models\Center;
use App\Models\Course;
use App\Models\User;
use App\Models\Video;
use App\Services\Settings\AdminSettingsPreviewService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class SettingsPreviewController extends Controller
{
    public function __construct(
        private readonly AdminSettingsPreviewService $previewService
    ) {}

    /**
     * Preview admin settings resolution.
     */
    public function __invoke(SettingsPreviewRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        $student = $this->loadStudent($request->integer('student_id'));
        $video = $this->loadVideo($request->integer('video_id'));
        $course = $this->loadCourse($request->integer('course_id'));
        $center = $this->loadCenter($request->integer('center_id'));

        $settings = $this->previewService->resolve($admin, $student, $video, $course, $center);

        return response()->json([
            'success' => true,
            'message' => 'Settings resolved successfully',
            'data' => $settings,
        ]);
    }

    private function loadStudent(?int $id): ?User
    {
        if ($id === null) {
            return null;
        }

        return User::with('studentSetting')->find($id);
    }

    private function loadVideo(?int $id): ?Video
    {
        if ($id === null) {
            return null;
        }

        return Video::with(['creator', 'setting', 'courses.center.setting'])->find($id);
    }

    private function loadCourse(?int $id): ?Course
    {
        if ($id === null) {
            return null;
        }

        return Course::with(['setting', 'center.setting'])->find($id);
    }

    private function loadCenter(?int $id): ?Center
    {
        if ($id === null) {
            return null;
        }

        return Center::with('setting')->find($id);
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
