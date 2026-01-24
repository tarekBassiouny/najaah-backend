<?php

declare(strict_types=1);

namespace App\Services\Pdfs;

use App\Enums\PdfUploadStatus;
use App\Exceptions\UploadFailedException;
use App\Models\Center;
use App\Models\PdfUploadSession;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use App\Services\Pdfs\Contracts\PdfUploadSessionServiceInterface;
use App\Services\Storage\Contracts\StorageServiceInterface;
use App\Services\Storage\StoragePathResolver;
use Illuminate\Support\Facades\Log;

class PdfUploadSessionService implements PdfUploadSessionServiceInterface
{
    public function __construct(
        private readonly StorageServiceInterface $storageService,
        private readonly StoragePathResolver $pathResolver,
        private readonly CenterScopeService $centerScopeService
    ) {}

    public function initialize(Center $center, User $admin, string $originalFilename, ?int $fileSizeKb = null): PdfUploadSession
    {
        if (! $admin->hasRole('super_admin')) {
            $this->centerScopeService->assertAdminCenterId($admin, $center->id);
        }

        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $extension = $extension !== '' ? strtolower($extension) : 'pdf';

        $filename = sprintf('%s.%s', uniqid('pdf_', true), $extension);
        $objectKey = $this->pathResolver->pdf($center->id, $filename);

        $ttl = (int) config('pdf.upload_url_ttl', 10800);
        $expiresAt = now()->addSeconds($ttl);

        $session = PdfUploadSession::create([
            'center_id' => $center->id,
            'created_by' => $admin->id,
            'object_key' => $objectKey,
            'upload_status' => PdfUploadStatus::Pending,
            'error_message' => null,
            'file_extension' => $extension,
            'file_size_kb' => $fileSizeKb,
            'expires_at' => $expiresAt,
        ]);

        $session->setAttribute('upload_url', $this->storageService->temporaryUploadUrl($objectKey, $ttl, 'application/pdf'));

        Log::channel('domain')->info('pdf_upload_session_created', [
            'session_id' => $session->id,
            'center_id' => $center->id,
        ]);

        return $session;
    }

    public function finalize(PdfUploadSession $session, User $admin, ?string $errorMessage = null): PdfUploadSession
    {
        if (! $admin->hasRole('super_admin')) {
            $this->centerScopeService->assertAdminCenterId($admin, $session->center_id);
        }

        if ($session->upload_status === PdfUploadStatus::Failed) {
            return $session;
        }

        if ($session->expires_at !== null && $session->expires_at <= now()) {
            $session->upload_status = PdfUploadStatus::Failed;
            $session->error_message = $errorMessage ?? 'Upload session expired.';
            $session->save();

            Log::channel('domain')->warning('pdf_upload_session_failed', [
                'session_id' => $session->id,
                'center_id' => $session->center_id,
            ]);

            throw new UploadFailedException($session->error_message ?? 'Upload failed.', 422);
        }

        if ($session->file_size_kb === null || $session->file_size_kb < 1) {
            $session->upload_status = PdfUploadStatus::Failed;
            $session->error_message = $errorMessage ?? 'Uploaded file size is invalid.';
            $session->save();

            Log::channel('domain')->warning('pdf_upload_session_failed', [
                'session_id' => $session->id,
                'center_id' => $session->center_id,
            ]);

            throw new UploadFailedException($session->error_message ?? 'Upload failed.', 422);
        }

        if (! $this->storageService->exists($session->object_key)) {
            $session->upload_status = PdfUploadStatus::Failed;
            $session->error_message = $errorMessage ?? 'Uploaded object not found.';
            $session->save();

            Log::channel('domain')->warning('pdf_upload_session_failed', [
                'session_id' => $session->id,
                'center_id' => $session->center_id,
            ]);

            throw new UploadFailedException($session->error_message ?? 'Upload failed.', 422);
        }

        $session->upload_status = PdfUploadStatus::Ready;
        $session->error_message = null;
        $session->save();

        Log::channel('domain')->info('pdf_upload_session_finalized', [
            'session_id' => $session->id,
            'center_id' => $session->center_id,
        ]);

        return $session;
    }
}
