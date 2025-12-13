<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SettingsPreviewRequest;
use App\Models\Center;
use App\Models\Course;
use App\Models\User;
use App\Models\Video;
use App\Services\Settings\Contracts\SettingsResolverServiceInterface;
use Illuminate\Http\JsonResponse;

class SettingsPreviewController extends Controller
{
    public function __construct(
        private readonly SettingsResolverServiceInterface $resolver
    ) {}

    public function __invoke(SettingsPreviewRequest $request): JsonResponse
    {
        $student = $this->loadStudent($request->integer('student_id'));
        $video = $this->loadVideo($request->integer('video_id'));
        $course = $this->loadCourse($request->integer('course_id'));
        $center = $this->loadCenter($request->integer('center_id'));

        $settings = $this->resolver->resolve($student, $video, $course, $center);

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

        return Video::with(['setting', 'courses.center.setting'])->find($id);
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
}
