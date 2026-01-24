<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Pdfs;

use App\Http\Controllers\Concerns\AdminAuthenticates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Pdfs\FinalizePdfUploadSessionRequest;
use App\Http\Requests\Admin\Pdfs\StorePdfUploadSessionRequest;
use App\Models\Center;
use App\Models\Pdf;
use App\Models\PdfUploadSession;
use App\Services\Pdfs\Contracts\PdfServiceInterface;
use App\Services\Pdfs\Contracts\PdfUploadSessionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PdfUploadSessionController extends Controller
{
    use AdminAuthenticates;

    public function __construct(
        private readonly PdfUploadSessionServiceInterface $uploadSessionService,
        private readonly PdfServiceInterface $pdfService
    ) {}

    public function store(StorePdfUploadSessionRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();

        $session = $this->uploadSessionService->initialize(
            $center,
            $admin,
            (string) $request->input('original_filename'),
            $request->integer('file_size_kb')
        );

        /** @var string|null $uploadUrl */
        $uploadUrl = $session->getAttribute('upload_url');
        /** @var \DateTimeInterface|null $expiresAt */
        $expiresAt = $session->expires_at;
        $expiresAtString = $expiresAt?->format(DATE_ATOM);

        return response()->json([
            'success' => true,
            'data' => [
                'upload_session_id' => $session->id,
                'provider' => 'spaces',
                'remote_id' => $session->object_key,
                'upload_endpoint' => $uploadUrl,
                'required_headers' => [
                    'Content-Type' => 'application/pdf',
                ],
                'expires_at' => $expiresAtString,
            ],
        ], 201);
    }

    public function finalize(
        FinalizePdfUploadSessionRequest $request,
        Center $center,
        PdfUploadSession $pdfUploadSession
    ): JsonResponse {
        $admin = $this->requireAdmin();

        if ((int) $pdfUploadSession->center_id !== (int) $center->id) {
            $this->notFound('PDF upload session not found.');
        }

        /** @var array<string, mixed> $data */
        $data = $request->validated();

        $session = $this->uploadSessionService->finalize(
            $pdfUploadSession,
            $admin,
            isset($data['error_message']) && is_string($data['error_message']) ? $data['error_message'] : null
        );

        $pdfId = isset($data['pdf_id']) && is_numeric($data['pdf_id']) ? (int) $data['pdf_id'] : null;

        if ($pdfId !== null) {
            $pdf = Pdf::findOrFail($pdfId);
            if ((int) $pdf->center_id !== (int) $center->id) {
                $this->notFound('PDF upload session not found.');
            }

            if ($pdf->upload_session_id !== null && (int) $pdf->upload_session_id !== (int) $session->id) {
                throw ValidationException::withMessages([
                    'pdf_id' => ['PDF is linked to a different upload session.'],
                ]);
            }

            $pdf->update([
                'upload_session_id' => $session->id,
                'source_type' => 1,
                'source_provider' => 'spaces',
                'source_id' => $session->object_key,
                'source_url' => null,
                'file_extension' => $session->file_extension,
                'file_size_kb' => $session->file_size_kb,
            ]);
        } else {
            $payload = $data;
            $payload['upload_session_id'] = $session->id;
            $this->pdfService->create($center, $admin, $payload);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'upload_session_id' => $session->id,
                'upload_status' => $session->upload_status,
                'error_message' => $session->error_message,
            ],
        ]);
    }
}
