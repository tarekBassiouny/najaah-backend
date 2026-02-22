<?php

declare(strict_types=1);

namespace App\Actions\Admin\Courses;

use App\Models\Course;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Storage\Contracts\StorageServiceInterface;
use App\Services\Storage\StoragePathResolver;
use App\Support\AuditActions;
use Illuminate\Http\UploadedFile;

class UploadCourseThumbnailAction
{
    public function __construct(
        private readonly StorageServiceInterface $storageService,
        private readonly StoragePathResolver $pathResolver,
        private readonly AuditLogService $auditLogService
    ) {}

    public function execute(Course $course, UploadedFile $thumbnail, ?User $actor = null): Course
    {
        $path = $this->pathResolver->courseThumbnail(
            (int) $course->center_id,
            (int) $course->id,
            $thumbnail->hashName()
        );
        $storedPath = $this->storageService->upload($path, $thumbnail);

        $course->thumbnail_url = $storedPath;
        $course->save();

        $this->auditLogService->log($actor, $course, AuditActions::COURSE_THUMBNAIL_UPDATED, [
            'thumbnail_url' => $storedPath,
        ]);

        return $course->fresh(['center', 'category', 'primaryInstructor', 'instructors']) ?? $course;
    }
}
