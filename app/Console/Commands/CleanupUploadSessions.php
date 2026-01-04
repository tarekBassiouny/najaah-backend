<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PdfUploadSession;
use App\Models\VideoUploadSession;
use App\Services\Pdfs\PdfUploadSessionService;
use App\Services\Videos\VideoUploadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupUploadSessions extends Command
{
    protected $signature = 'uploads:cleanup';

    protected $description = 'Delete expired or failed upload sessions for videos and PDFs.';

    public function handle(): int
    {
        $now = now();
        $videoTtlDays = (int) config('uploads.video_ttl_days', 3);
        $pdfTtlDays = (int) config('uploads.pdf_ttl_days', 3);

        $videoCutoff = $now->copy()->subDays($videoTtlDays);
        $pdfCutoff = $now->copy()->subDays($pdfTtlDays);

        $videoQuery = VideoUploadSession::query()
            ->where('upload_status', '!=', VideoUploadService::STATUS_READY)
            ->where(function ($query) use ($now, $videoCutoff): void {
                $query
                    ->where(function ($sub) use ($now): void {
                        $sub->whereNotNull('expires_at')
                            ->where('expires_at', '<', $now);
                    })
                    ->orWhereIn('upload_status', [VideoUploadService::STATUS_FAILED])
                    ->orWhere(function ($sub) use ($videoCutoff): void {
                        $sub->where('created_at', '<', $videoCutoff)
                            ->where('upload_status', '!=', VideoUploadService::STATUS_READY);
                    });
            })
            ->whereDoesntHave('videos.courses', function ($query): void {
                $query->where('is_published', true);
            });

        $deletedVideoSessions = $videoQuery->delete();

        $pdfQuery = PdfUploadSession::query()
            ->where('upload_status', '!=', PdfUploadSessionService::STATUS_READY)
            ->where(function ($query) use ($now, $pdfCutoff): void {
                $query
                    ->where(function ($sub) use ($now): void {
                        $sub->whereNotNull('expires_at')
                            ->where('expires_at', '<', $now);
                    })
                    ->orWhere(function ($sub) use ($pdfCutoff): void {
                        $sub->where('created_at', '<', $pdfCutoff)
                            ->where('upload_status', '!=', PdfUploadSessionService::STATUS_READY);
                    });
            })
            ->whereDoesntHave('pdfs.courses', function ($query): void {
                $query->where('is_published', true);
            });

        $deletedPdfSessions = $pdfQuery->delete();

        Log::channel('domain')->info('upload_sessions_cleanup', [
            'deleted_video_sessions' => $deletedVideoSessions,
            'deleted_pdf_sessions' => $deletedPdfSessions,
        ]);

        $this->info(sprintf(
            'Deleted video sessions: %d, pdf sessions: %d',
            $deletedVideoSessions,
            $deletedPdfSessions
        ));

        return Command::SUCCESS;
    }
}
