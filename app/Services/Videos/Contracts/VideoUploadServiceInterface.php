<?php

declare(strict_types=1);

namespace App\Services\Videos\Contracts;

use App\Models\Center;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUploadSession;

interface VideoUploadServiceInterface
{
    public function initializeUpload(User $admin, Center $center, string $originalFilename, ?Video $video = null): VideoUploadSession;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function transition(User $admin, VideoUploadSession $session, string $statusLabel, array $payload): VideoUploadSession;
}
