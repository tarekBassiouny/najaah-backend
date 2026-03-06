<?php

declare(strict_types=1);

namespace App\Actions\Admin\Instructors;

use App\Models\Instructor;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Storage\Contracts\StorageServiceInterface;
use App\Services\Storage\StoragePathResolver;
use App\Support\AuditActions;
use Illuminate\Http\UploadedFile;

class UploadInstructorAvatarAction
{
    public function __construct(
        private readonly StorageServiceInterface $storageService,
        private readonly StoragePathResolver $pathResolver,
        private readonly AuditLogService $auditLogService
    ) {}

    public function execute(Instructor $instructor, UploadedFile $avatar, ?User $actor = null): Instructor
    {
        $previousPath = is_string($instructor->avatar_url) ? $instructor->avatar_url : null;
        $path = $this->pathResolver->instructorAvatar((int) $instructor->center_id, $avatar->hashName());
        $storedPath = $this->storageService->upload($path, $avatar);

        $instructor->avatar_url = $storedPath;
        $instructor->save();

        if ($previousPath !== null && $previousPath !== '' && $previousPath !== $storedPath && ! $this->isAbsoluteUrl($previousPath)) {
            $this->storageService->delete($previousPath);
        }

        $this->auditLogService->log($actor, $instructor, AuditActions::INSTRUCTOR_AVATAR_UPDATED, [
            'avatar_url' => $storedPath,
        ]);

        return $instructor->fresh(['center', 'creator', 'courses']) ?? $instructor;
    }

    private function isAbsoluteUrl(string $path): bool
    {
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
    }
}
