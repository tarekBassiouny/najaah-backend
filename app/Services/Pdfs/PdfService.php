<?php

declare(strict_types=1);

namespace App\Services\Pdfs;

use App\Enums\MediaSourceType;
use App\Models\Center;
use App\Models\Pdf;
use App\Models\PdfUploadSession;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Pdfs\Contracts\PdfServiceInterface;
use App\Support\AuditActions;
use App\Support\Guards\RejectNonScalarInput;
use Illuminate\Validation\ValidationException;

class PdfService implements PdfServiceInterface
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly AuditLogService $auditLogService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Center $center, User $admin, array $data): Pdf
    {
        if (! $admin->hasRole('super_admin')) {
            $this->centerScopeService->assertAdminCenterId($admin, $center->id);
        }

        RejectNonScalarInput::validate($data, ['title', 'description']);
        $payload = $data;
        // Support legacy 'title'/'description' fields by mapping to '_translations'
        if (array_key_exists('title', $payload) && ! array_key_exists('title_translations', $payload)) {
            $payload['title_translations'] = $payload['title'];
        }

        if (array_key_exists('description', $payload) && ! array_key_exists('description_translations', $payload)) {
            $payload['description_translations'] = $payload['description'];
        }

        unset($payload['title'], $payload['description']);

        $payload['center_id'] = $center->id;
        $payload['created_by'] = $admin->id;

        if (isset($data['upload_session_id'])) {
            $session = PdfUploadSession::findOrFail((int) $data['upload_session_id']);

            if ((int) $session->center_id !== (int) $center->id) {
                throw ValidationException::withMessages([
                    'upload_session_id' => ['Upload session not found.'],
                ]);
            }

            $payload['source_type'] = MediaSourceType::Upload;
            $payload['source_provider'] = 'spaces';
            $payload['source_id'] = $session->object_key;
            $payload['source_url'] = null;
            $payload['file_extension'] = $session->file_extension;
            $payload['file_size_kb'] = $session->file_size_kb;
            $payload['upload_session_id'] = $session->id;
        } else {
            throw ValidationException::withMessages([
                'upload_session_id' => ['Upload session is required for PDF creation.'],
            ]);
        }

        /** @var Pdf $pdf */
        $pdf = Pdf::create($payload);

        $this->auditLogService->log($admin, $pdf, AuditActions::PDF_CREATED, [
            'center_id' => $center->id,
        ]);

        return $pdf;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Pdf $pdf, User $admin, array $data): Pdf
    {
        if (! $admin->hasRole('super_admin')) {
            $this->centerScopeService->assertAdminCenterId($admin, $pdf->center_id);
        }

        RejectNonScalarInput::validate($data, ['title', 'description']);
        $payload = $data;
        if (array_key_exists('title', $payload)) {
            $payload['title_translations'] = $payload['title'];
            unset($payload['title']);
        }

        if (array_key_exists('description', $payload)) {
            $payload['description_translations'] = $payload['description'];
            unset($payload['description']);
        }

        $pdf->update($payload);

        $this->auditLogService->log($admin, $pdf, AuditActions::PDF_UPDATED, [
            'updated_fields' => array_keys($payload),
        ]);

        return $pdf->fresh(['creator']) ?? $pdf;
    }

    public function delete(Pdf $pdf, User $admin): void
    {
        if (! $admin->hasRole('super_admin')) {
            $this->centerScopeService->assertAdminCenterId($admin, $pdf->center_id);
        }

        $pdf->delete();

        $this->auditLogService->log($admin, $pdf, AuditActions::PDF_DELETED);
    }
}
