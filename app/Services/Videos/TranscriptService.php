<?php

declare(strict_types=1);

namespace App\Services\Videos;

use App\Enums\TranscriptFormat;
use App\Enums\TranscriptSource;
use App\Models\User;
use App\Models\Video;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Videos\Contracts\TranscriptServiceInterface;
use App\Support\AuditActions;
use Illuminate\Http\UploadedFile;
use RuntimeException;

final class TranscriptService implements TranscriptServiceInterface
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly AuditLogService $auditLogService
    ) {}

    public function uploadTranscript(Video $video, User $admin, UploadedFile $file): Video
    {
        $this->authorize($video, $admin);

        $extension = strtolower($file->getClientOriginalExtension());
        $format = TranscriptFormat::tryFrom($extension);
        if (! $format instanceof TranscriptFormat) {
            throw new RuntimeException('Unsupported transcript format.');
        }

        $contents = $file->get();
        if (! is_string($contents)) {
            throw new RuntimeException('Unable to read transcript file.');
        }

        $plainText = $this->parseToPlainText($contents, $format);

        $video->update([
            'transcript' => $plainText,
            'transcript_format' => $format,
            'transcript_source' => TranscriptSource::Manual,
        ]);

        $this->auditLogService->log($admin, $video, AuditActions::VIDEO_UPDATED, [
            'updated_fields' => ['transcript', 'transcript_format', 'transcript_source'],
        ]);

        return $video->fresh(['center', 'creator']) ?? $video;
    }

    public function saveTranscriptText(Video $video, User $admin, string $transcriptText): Video
    {
        $this->authorize($video, $admin);

        $plainText = $this->normalizePlainText($transcriptText);

        $video->update([
            'transcript' => $plainText,
            'transcript_format' => TranscriptFormat::Txt,
            'transcript_source' => TranscriptSource::Manual,
        ]);

        $this->auditLogService->log($admin, $video, AuditActions::VIDEO_UPDATED, [
            'updated_fields' => ['transcript', 'transcript_format', 'transcript_source'],
        ]);

        return $video->fresh(['center', 'creator']) ?? $video;
    }

    public function getTranscript(Video $video): ?string
    {
        return is_string($video->transcript ?? null) && trim((string) $video->transcript) !== ''
            ? (string) $video->transcript
            : null;
    }

    public function deleteTranscript(Video $video, User $admin): Video
    {
        $this->authorize($video, $admin);

        $video->update([
            'transcript' => null,
            'transcript_format' => null,
            'transcript_source' => null,
        ]);

        $this->auditLogService->log($admin, $video, AuditActions::VIDEO_UPDATED, [
            'updated_fields' => ['transcript', 'transcript_format', 'transcript_source'],
        ]);

        return $video->fresh(['center', 'creator']) ?? $video;
    }

    public function parseToPlainText(string $content, TranscriptFormat $format): string
    {
        return match ($format) {
            TranscriptFormat::Txt => $this->normalizePlainText($content),
            TranscriptFormat::Vtt => $this->normalizePlainText($this->stripVttMarkup($content)),
            TranscriptFormat::Srt => $this->normalizePlainText($this->stripSrtMarkup($content)),
        };
    }

    private function authorize(Video $video, User $admin): void
    {
        if (! $this->centerScopeService->isSystemSuperAdmin($admin)) {
            $this->centerScopeService->assertAdminCenterId($admin, $video->center_id);
        }
    }

    private function stripVttMarkup(string $content): string
    {
        $lines = preg_split('/\R/u', $content) ?: [];
        $filtered = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || $trimmed === 'WEBVTT') {
                continue;
            }

            if (preg_match('/^\d{2}:\d{2}:\d{2}\.\d{3}\s*-->/', $trimmed) === 1) {
                continue;
            }

            if (preg_match('/^\d+$/', $trimmed) === 1) {
                continue;
            }

            $filtered[] = $line;
        }

        return implode("\n", $filtered);
    }

    private function stripSrtMarkup(string $content): string
    {
        $lines = preg_split('/\R/u', $content) ?: [];
        $filtered = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            if (preg_match('/^\d+$/', $trimmed) === 1) {
                continue;
            }

            if (preg_match('/^\d{2}:\d{2}:\d{2},\d{3}\s*-->/', $trimmed) === 1) {
                continue;
            }

            $filtered[] = $line;
        }

        return implode("\n", $filtered);
    }

    private function normalizePlainText(string $content): string
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $content = preg_replace("/\n{3,}/", "\n\n", $content) ?? $content;

        return trim($content);
    }
}
