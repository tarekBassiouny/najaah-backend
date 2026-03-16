<?php

declare(strict_types=1);

namespace App\Services\VideoAccess\Contracts;

use App\Models\Course;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoCodeBatch;
use Generator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface VideoCodeBatchServiceInterface
{
    /**
     * Create a new batch of video access codes.
     */
    public function createBatch(
        User $admin,
        Video $video,
        Course $course,
        int $quantity,
        int $viewLimitPerCode = 2
    ): VideoCodeBatch;

    /**
     * Add more codes to an existing open batch.
     */
    public function expandBatch(
        User $admin,
        VideoCodeBatch $batch,
        int $additionalQuantity
    ): VideoCodeBatch;

    /**
     * Close a batch and set, or later increase, the sold limit.
     */
    public function closeBatch(
        User $admin,
        VideoCodeBatch $batch,
        int $soldLimit
    ): VideoCodeBatch;

    /**
     * Generate codes on-the-fly for export.
     *
     * @return Generator<int, string>
     */
    public function generateCodesForExport(VideoCodeBatch $batch, ?int $startSequence = null, ?int $endSequence = null): Generator;

    /**
     * Export batch codes as CSV.
     *
     * @param  int|null  $startSequence  Start from this sequence number (1-based)
     * @param  int|null  $endSequence  End at this sequence number (1-based)
     */
    public function exportAsCsv(
        User $admin,
        VideoCodeBatch $batch,
        ?int $startSequence = null,
        ?int $endSequence = null
    ): StreamedResponse;

    /**
     * Export batch codes as PDF with QR codes.
     * Limited to 500 codes per request to prevent memory issues.
     *
     * @param  int|null  $startSequence  Start from this sequence number (1-based)
     * @param  int|null  $endSequence  End at this sequence number (1-based)
     */
    public function exportAsPdf(
        User $admin,
        VideoCodeBatch $batch,
        ?int $startSequence = null,
        ?int $endSequence = null,
        int $cardsPerPage = 8
    ): StreamedResponse;

    /**
     * Queue a CSV export delivery to a WhatsApp destination and return the history record.
     *
     * @return array<string, mixed>
     */
    public function queueCsvWhatsAppSend(
        User $admin,
        VideoCodeBatch $batch,
        string $phoneNumber,
        ?int $startSequence = null,
        ?int $endSequence = null
    ): array;

    /**
     * Process a queued WhatsApp CSV delivery for a batch export history record.
     */
    public function processCsvWhatsAppSend(
        VideoCodeBatch $batch,
        string $historyRecordId,
        string $phoneNumber
    ): void;

    /**
     * Get batch statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(VideoCodeBatch $batch): array;

    /**
     * Get paginated batch redemptions for admin details screens.
     *
     * @return LengthAwarePaginator<\App\Models\VideoCodeRedemption>
     */
    public function getRedemptions(
        VideoCodeBatch $batch,
        int $perPage = 15,
        int $page = 1,
        ?string $search = null
    ): LengthAwarePaginator;

    /**
     * Get the active (open) batch for a course video, if any.
     */
    public function getActiveBatch(Course $course, Video $video): ?VideoCodeBatch;
}
