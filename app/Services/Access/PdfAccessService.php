<?php

declare(strict_types=1);

namespace App\Services\Access;

use App\Enums\PdfUploadStatus;
use App\Exceptions\AttachmentNotAllowedException;
use App\Exceptions\UploadNotReadyException;
use App\Models\Pdf;
use Illuminate\Support\Facades\Log;

class PdfAccessService
{
    public function assertReadyForAttachment(Pdf $pdf): void
    {
        if ($pdf->upload_session_id === null) {
            throw new AttachmentNotAllowedException('PDF is not ready to be attached.', 422);
        }

        $pdf->loadMissing('uploadSession');
        $session = $pdf->uploadSession;

        if ($session === null) {
            throw new UploadNotReadyException('PDF upload session is required.', 422);
        }

        if ($session->expires_at !== null && $session->expires_at <= now()) {
            Log::channel('domain')->warning('upload_session_expired', [
                'pdf_id' => $pdf->id,
                'session_id' => $session->id,
            ]);
            throw new UploadNotReadyException('PDF upload session has expired.', 422);
        }

        if ($session->upload_status !== PdfUploadStatus::Ready) {
            throw new UploadNotReadyException('PDF upload session is not ready.', 422);
        }
    }
}
