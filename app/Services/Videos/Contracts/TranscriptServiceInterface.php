<?php

declare(strict_types=1);

namespace App\Services\Videos\Contracts;

use App\Enums\TranscriptFormat;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\UploadedFile;

interface TranscriptServiceInterface
{
    public function uploadTranscript(Video $video, User $admin, UploadedFile $file): Video;

    public function saveTranscriptText(Video $video, User $admin, string $transcriptText): Video;

    public function getTranscript(Video $video): ?string;

    public function deleteTranscript(Video $video, User $admin): Video;

    public function parseToPlainText(string $content, TranscriptFormat $format): string;
}
