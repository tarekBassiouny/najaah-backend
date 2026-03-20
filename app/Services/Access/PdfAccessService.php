<?php

declare(strict_types=1);

namespace App\Services\Access;

use App\Enums\PdfUploadStatus;
use App\Exceptions\AttachmentNotAllowedException;
use App\Exceptions\UploadNotReadyException;
use App\Models\Pdf;

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

        if ($session->upload_status !== PdfUploadStatus::Ready) {
            throw new UploadNotReadyException('PDF upload session is not ready.', 422);
        }
    }
}
