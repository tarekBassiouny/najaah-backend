<?php

declare(strict_types=1);

namespace App\Services\VideoAccess;

use App\Enums\VideoCodeBatchStatus;
use App\Exceptions\DomainException;
use App\Jobs\SendVideoCodeBatchCsvToWhatsAppJob;
use App\Models\Center;
use App\Models\Course;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoCodeBatch;
use App\Models\VideoCodeRedemption;
use App\Services\Centers\CenterScopeService;
use App\Services\Evolution\EvolutionApiClient;
use App\Services\Settings\PolicySettingsService;
use App\Services\VideoAccess\Contracts\VideoCodeBatchServiceInterface;
use App\Support\ErrorCodes;
use App\Support\PhoneSearch;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Generator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoCodeBatchService implements VideoCodeBatchServiceInterface
{
    /**
     * Maximum codes allowed per PDF export to prevent memory issues.
     */
    private const MAX_PDF_EXPORT_CODES = 500;

    /**
     * Supported printable PDF layouts keyed by cards per page.
     *
     * @var array<int, array{cards_per_page:int,card_height_mm:int,qr_display_mm:int,qr_size_px:int,code_font_px:int,meta_font_px:int}>
     */
    private const PDF_LAYOUTS = [
        4 => [
            'cards_per_page' => 4,
            'card_height_mm' => 104,
            'qr_display_mm' => 31,
            'qr_size_px' => 144,
            'code_font_px' => 15,
            'meta_font_px' => 10,
        ],
        6 => [
            'cards_per_page' => 6,
            'card_height_mm' => 71,
            'qr_display_mm' => 23,
            'qr_size_px' => 128,
            'code_font_px' => 13,
            'meta_font_px' => 9,
        ],
        8 => [
            'cards_per_page' => 8,
            'card_height_mm' => 52,
            'qr_display_mm' => 18,
            'qr_size_px' => 110,
            'code_font_px' => 11,
            'meta_font_px' => 8,
        ],
    ];

    public function __construct(
        private readonly VideoCodeGenerator $codeGenerator,
        private readonly CenterScopeService $centerScopeService,
        private readonly EvolutionApiClient $evolutionApiClient,
        private readonly PolicySettingsService $policySettingsService,
        private readonly PhoneSearch $phoneSearch
    ) {}

    public function createBatch(
        User $admin,
        Video $video,
        Course $course,
        int $quantity,
        int $viewLimitPerCode = 2
    ): VideoCodeBatch {
        if ($admin->is_student) {
            $this->deny(ErrorCodes::UNAUTHORIZED, 'Only admins can create code batches.', 403);
        }

        $this->centerScopeService->assertAdminSameCenter($admin, $course);
        $this->assertCourseSupportsVideoCodes($course);
        $this->assertVideoBelongsToCourse($video, $course);

        $center = $course->center;
        $policy = $this->policySettingsService->resolveCenterPolicy($center);
        $catalog = $this->policySettingsService->catalog();
        $maxQuantity = (int) ($policy['video_code_batch_max_quantity'] ?? $catalog['video_code_batch_max_quantity']['default']);
        $maxViewLimit = (int) ($policy['max_video_code_batch_view_limit'] ?? $catalog['max_video_code_batch_view_limit']['default']);

        if ($quantity < 1 || $quantity > $maxQuantity) {
            $this->deny(ErrorCodes::INVALID_STATE, sprintf('Quantity must be between 1 and %d.', $maxQuantity), 422);
        }

        if ($viewLimitPerCode < 1 || $viewLimitPerCode > $maxViewLimit) {
            $this->deny(ErrorCodes::INVALID_VIEWS, sprintf('View limit must be between 1 and %d.', $maxViewLimit), 422);
        }

        return DB::transaction(function () use ($admin, $video, $course, $quantity, $viewLimitPerCode): VideoCodeBatch {
            // Lock and check for existing open batch atomically
            $existingOpen = VideoCodeBatch::query()
                ->forCourseVideo($course->id, $video->id)
                ->open()
                ->notDeleted()
                ->lockForUpdate()
                ->exists();

            if ($existingOpen) {
                $this->deny(
                    ErrorCodes::VIDEO_CODE_BATCH_ACTIVE_EXISTS,
                    'An open batch already exists for this course video. Expand the existing batch or close it first.',
                    422
                );
            }

            $batchCode = $this->codeGenerator->generateBatchCode((int) $course->center_id);
            $secretKey = $this->codeGenerator->generateSecretKey();

            /** @var VideoCodeBatch $batch */
            $batch = VideoCodeBatch::query()->create([
                'video_id' => $video->id,
                'course_id' => $course->id,
                'center_id' => $course->center_id,
                'batch_code' => $batchCode,
                'secret_key' => $secretKey,
                'quantity' => $quantity,
                'view_limit_per_code' => $viewLimitPerCode,
                'status' => VideoCodeBatchStatus::Open,
                'generated_by' => $admin->id,
                'generated_at' => Carbon::now(),
                'metadata' => [
                    'exports' => [],
                ],
            ]);

            return $batch->fresh(['video', 'course', 'center', 'generator']) ?? $batch;
        });
    }

    public function expandBatch(
        User $admin,
        VideoCodeBatch $batch,
        int $additionalQuantity
    ): VideoCodeBatch {
        if ($admin->is_student) {
            $this->deny(ErrorCodes::UNAUTHORIZED, 'Only admins can expand code batches.', 403);
        }

        $this->centerScopeService->assertAdminSameCenter($admin, $batch);

        $center = $batch->relationLoaded('center') ? $batch->center : $batch->center()->first();
        $policy = $center instanceof Center
            ? $this->policySettingsService->resolveCenterPolicy($center)
            : [];
        $catalog = $this->policySettingsService->catalog();
        $maxQuantity = (int) ($policy['video_code_batch_max_quantity'] ?? $catalog['video_code_batch_max_quantity']['default']);

        if ($additionalQuantity < 1 || $additionalQuantity > $maxQuantity) {
            $this->deny(ErrorCodes::INVALID_STATE, sprintf('Additional quantity must be between 1 and %d.', $maxQuantity), 422);
        }

        return DB::transaction(function () use ($batch, $additionalQuantity, $maxQuantity): VideoCodeBatch {
            // Lock and recheck status
            /** @var VideoCodeBatch|null $lockedBatch */
            $lockedBatch = VideoCodeBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedBatch instanceof VideoCodeBatch) {
                $this->deny(ErrorCodes::VIDEO_CODE_BATCH_NOT_FOUND, 'Batch not found.', 404);
            }

            if ($lockedBatch->isClosed()) {
                $this->deny(ErrorCodes::VIDEO_CODE_BATCH_CLOSED, 'Cannot expand a closed batch.', 422);
            }

            $newTotal = $lockedBatch->quantity + $additionalQuantity;
            if ($newTotal > $maxQuantity) {
                $this->deny(ErrorCodes::INVALID_STATE, sprintf('Total batch size cannot exceed %d codes.', $maxQuantity), 422);
            }

            $lockedBatch->quantity = $newTotal;
            $lockedBatch->save();

            return $lockedBatch->fresh(['video', 'course', 'center', 'generator']) ?? $lockedBatch;
        });
    }

    public function closeBatch(
        User $admin,
        VideoCodeBatch $batch,
        int $soldLimit
    ): VideoCodeBatch {
        if ($admin->is_student) {
            $this->deny(ErrorCodes::UNAUTHORIZED, 'Only admins can close code batches.', 403);
        }

        $this->centerScopeService->assertAdminSameCenter($admin, $batch);

        if ($soldLimit < 0) {
            $this->deny(ErrorCodes::INVALID_STATE, 'Sold limit cannot be negative.', 422);
        }

        return DB::transaction(function () use ($admin, $batch, $soldLimit): VideoCodeBatch {
            /** @var VideoCodeBatch|null $lockedBatch */
            $lockedBatch = VideoCodeBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedBatch instanceof VideoCodeBatch) {
                $this->deny(ErrorCodes::VIDEO_CODE_BATCH_NOT_FOUND, 'Batch not found.', 404);
            }

            $this->assertSoldLimitIsValid($lockedBatch, $soldLimit);

            if ($lockedBatch->isClosed()) {
                $currentSoldLimit = $lockedBatch->sold_limit;

                if ($currentSoldLimit !== null && $soldLimit < $currentSoldLimit) {
                    $this->deny(
                        ErrorCodes::INVALID_STATE,
                        'Closed batch sold limit can only be increased.',
                        422
                    );
                }
            } else {
                $lockedBatch->status = VideoCodeBatchStatus::Closed;
                $lockedBatch->closed_at = Carbon::now();
                $lockedBatch->closed_by = $admin->id;
            }

            $lockedBatch->sold_limit = $soldLimit;

            if ($lockedBatch->isDirty()) {
                $lockedBatch->save();
            }

            return $lockedBatch->fresh(['video', 'course', 'center', 'generator', 'closer']) ?? $lockedBatch;
        });
    }

    public function generateCodesForExport(VideoCodeBatch $batch, ?int $startSequence = null, ?int $endSequence = null): Generator
    {
        $start = $startSequence ?? 1;
        $end = $endSequence ?? $batch->quantity;

        // Clamp to valid range
        $start = max(1, min($start, $batch->quantity));
        $end = max($start, min($end, $batch->quantity));

        for ($seq = $start; $seq <= $end; $seq++) {
            yield $seq => $this->codeGenerator->generateCode($batch, $seq);
        }
    }

    public function exportAsCsv(
        User $admin,
        VideoCodeBatch $batch,
        ?int $startSequence = null,
        ?int $endSequence = null
    ): StreamedResponse {
        $batch->loadMissing(['video', 'course']);

        $filename = $this->buildExportFilename($batch, 'csv');

        $this->recordExport($admin, $batch, 'csv', $startSequence, $endSequence);

        return new StreamedResponse(
            function () use ($batch, $startSequence, $endSequence): void {
                $output = fopen('php://output', 'w');
                if ($output === false) {
                    return;
                }

                $this->writeCsvRows($output, $batch, $startSequence, $endSequence);

                fclose($output);
            },
            200,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]
        );
    }

    public function exportAsPdf(
        User $admin,
        VideoCodeBatch $batch,
        ?int $startSequence = null,
        ?int $endSequence = null,
        int $cardsPerPage = 8
    ): StreamedResponse {
        $start = $startSequence ?? 1;
        $end = $endSequence ?? $batch->quantity;

        // Clamp to valid range
        $start = max(1, min($start, $batch->quantity));
        $end = max($start, min($end, $batch->quantity));

        $codeCount = $end - $start + 1;

        // Enforce PDF export limit to prevent memory issues
        if ($codeCount > self::MAX_PDF_EXPORT_CODES) {
            $this->deny(
                ErrorCodes::INVALID_STATE,
                sprintf(
                    'PDF export is limited to %d codes per request. Use start_sequence and end_sequence to export in chunks, or use CSV for larger exports.',
                    self::MAX_PDF_EXPORT_CODES
                ),
                422
            );
        }

        $batch->loadMissing(['video', 'course']);
        $filename = $this->buildExportFilename($batch, 'pdf');
        $layout = $this->resolvePdfLayout($cardsPerPage);

        /** @var Video|null $video */
        $video = $batch->video;
        /** @var Course|null $course */
        $course = $batch->course;

        $videoTitle = $video?->translate('title') ?? 'Video';
        $courseTitle = $course?->translate('title') ?? 'Course';
        $cardVideoTitle = Str::limit($videoTitle, 42, '...');
        $cardCourseTitle = Str::limit($courseTitle, 42, '...');

        $codes = [];
        $qrWriter = $this->createQrWriter($layout['qr_size_px']);

        foreach ($this->generateCodesForExport($batch, $start, $end) as $seq => $code) {
            $codes[] = [
                'sequence' => $seq,
                'code' => $code,
                'qr_code_data_url' => $this->generateQrCodeDataUrl($qrWriter, $code),
            ];
        }

        $pdf = Pdf::loadView('pdf.video-code-batches.export', [
            'batch' => $batch,
            'videoTitle' => $videoTitle,
            'courseTitle' => $courseTitle,
            'cardVideoTitle' => $cardVideoTitle,
            'cardCourseTitle' => $cardCourseTitle,
            'pages' => array_chunk($codes, max(1, (int) $layout['cards_per_page'])),
            'layout' => $layout,
            'rangeLabel' => sprintf('%d-%d', $start, $end),
            'exportedCount' => $codeCount,
        ])->setPaper('a4');

        $this->recordExport($admin, $batch, 'pdf', $start, $end);

        return response()->streamDownload(
            static function () use ($pdf): void {
                echo $pdf->output();
            },
            $filename,
            [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]
        );
    }

    public function queueCsvWhatsAppSend(
        User $admin,
        VideoCodeBatch $batch,
        string $phoneNumber,
        ?int $startSequence = null,
        ?int $endSequence = null
    ): array {
        if ($admin->is_student) {
            $this->deny(ErrorCodes::UNAUTHORIZED, 'Only admins can send batch exports via WhatsApp.', 403);
        }

        $this->centerScopeService->assertAdminSameCenter($admin, $batch);
        $batch->loadMissing(['video', 'course']);

        $range = $this->resolveSequenceRange($batch, $startSequence, $endSequence);
        $destination = $this->normalizeDestination($phoneNumber);

        $record = $this->appendExportRecord($admin, $batch, [
            'type' => 'whatsapp_csv',
            'format' => 'csv',
            'delivery_channel' => 'whatsapp',
            'status' => 'processing',
            'destination_masked' => $this->maskDestination($destination),
            'code_range' => sprintf('%d-%d', $range['start'], $range['end']),
            'start_sequence' => $range['start'],
            'end_sequence' => $range['end'],
            'count' => $range['count'],
            'file_name' => $this->buildExportFilename($batch, 'csv'),
        ]);

        SendVideoCodeBatchCsvToWhatsAppJob::dispatch(
            (int) $batch->id,
            (string) $record['id'],
            $destination
        );

        $current = $this->findExportRecord($batch->fresh() ?? $batch, (string) $record['id']);

        return $current ?? $record;
    }

    public function processCsvWhatsAppSend(
        VideoCodeBatch $batch,
        string $historyRecordId,
        string $phoneNumber
    ): void {
        $batch->loadMissing(['video', 'course']);

        /** @var array<string, mixed>|null $record */
        $record = $this->findExportRecord($batch, $historyRecordId);
        if (! is_array($record)) {
            return;
        }

        $start = is_numeric($record['start_sequence'] ?? null) ? (int) $record['start_sequence'] : 1;
        $end = is_numeric($record['end_sequence'] ?? null) ? (int) $record['end_sequence'] : $batch->quantity;
        $filename = is_string($record['file_name'] ?? null) && $record['file_name'] !== ''
            ? $record['file_name']
            : $this->buildExportFilename($batch, 'csv');

        $instanceName = (string) config('evolution.otp_instance_name', '');
        if ($instanceName === '') {
            $this->updateExportRecord($batch, $historyRecordId, [
                'status' => 'failed',
                'completed_at' => Carbon::now()->toIso8601String(),
                'error' => 'WhatsApp instance is not configured.',
            ]);

            return;
        }

        try {
            $csv = $this->buildCsvContent($batch, $start, $end);

            $this->evolutionApiClient->sendMedia($instanceName, [
                'number' => $phoneNumber,
                'mediatype' => 'document',
                'mimetype' => 'text/csv',
                'media' => base64_encode($csv),
                'fileName' => $filename,
                'caption' => sprintf(
                    "Video access codes CSV for '%s' (batch %s, range %d-%d).",
                    $batch->video->translate('title') ?? 'Video',
                    $batch->batch_code,
                    $start,
                    $end
                ),
            ]);

            $this->updateExportRecord($batch, $historyRecordId, [
                'status' => 'sent',
                'completed_at' => Carbon::now()->toIso8601String(),
                'error' => null,
            ]);
        } catch (\Throwable $throwable) {
            $this->updateExportRecord($batch, $historyRecordId, [
                'status' => 'failed',
                'completed_at' => Carbon::now()->toIso8601String(),
                'error' => 'Failed to send WhatsApp message: '.$this->resolveWhatsAppSendFailureMessage($throwable, $instanceName),
            ]);
        }
    }

    public function getStatistics(VideoCodeBatch $batch): array
    {
        $batch->loadMissing(['video', 'course']);

        $remaining = null;
        if ($batch->isClosed() && $batch->sold_limit !== null) {
            $remaining = max(0, $batch->sold_limit - $batch->redeemed_count);
        }

        $availableCount = max(0, $batch->quantity - $batch->redeemed_count);

        /** @var VideoCodeRedemption|null $firstRedemption */
        $firstRedemption = VideoCodeRedemption::query()
            ->where('batch_id', $batch->id)
            ->orderBy('redeemed_at')
            ->first();

        /** @var VideoCodeRedemption|null $lastRedemption */
        $lastRedemption = VideoCodeRedemption::query()
            ->where('batch_id', $batch->id)
            ->orderByDesc('redeemed_at')
            ->first();

        $recentRedemptions = VideoCodeRedemption::query()
            ->where('batch_id', $batch->id)
            ->with('user')
            ->orderByDesc('redeemed_at')
            ->limit(10)
            ->get()
            ->map(function (VideoCodeRedemption $redemption) use ($batch): array {
                $user = $redemption->user;
                $phone = null;

                if ($user !== null && is_string($user->phone) && $user->phone !== '') {
                    $phone = ($user->country_code ?? '').$user->phone;
                }

                return [
                    'id' => $redemption->id,
                    'sequence_number' => $redemption->sequence_number,
                    'code' => $this->codeGenerator->generateCode($batch, $redemption->sequence_number),
                    'user' => $user ? [
                        'id' => $user->id,
                        'name' => $user->name,
                        'phone' => $phone,
                    ] : null,
                    'redeemed_at' => $redemption->redeemed_at->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        $metadata = is_array($batch->metadata) ? $batch->metadata : [];
        $exportRecords = $metadata['exports'] ?? [];
        if (! is_array($exportRecords)) {
            $exportRecords = [];
        }

        $exports = array_values(array_map(fn (mixed $export): array => $this->normalizeExportRecord($export), $exportRecords));

        return [
            'batch_id' => $batch->id,
            'batch_code' => $batch->batch_code,
            'video_id' => $batch->video_id,
            'course_id' => $batch->course_id,
            'quantity' => $batch->quantity,
            'total_codes' => $batch->quantity,
            'sold_limit' => $batch->sold_limit,
            'redeemed_count' => $batch->redeemed_count,
            'available_count' => $availableCount,
            'remaining' => $remaining,
            'status' => strtolower($batch->status->name),
            'is_open' => $batch->isOpen(),
            'first_redemption_at' => $firstRedemption?->redeemed_at?->toIso8601String(),
            'last_redemption_at' => $lastRedemption?->redeemed_at?->toIso8601String(),
            'exports' => $exports,
            'recent_redemptions' => $recentRedemptions,
        ];
    }

    public function getRedemptions(
        VideoCodeBatch $batch,
        int $perPage = 15,
        int $page = 1,
        ?string $search = null
    ): LengthAwarePaginator {
        $query = VideoCodeRedemption::query()
            ->where('batch_id', $batch->id)
            ->with(['user', 'batch'])
            ->orderByDesc('redeemed_at');

        $term = is_string($search) ? trim($search) : '';
        if ($term !== '') {
            $this->applyRedemptionSearch($query, $batch, $term);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function getActiveBatch(Course $course, Video $video): ?VideoCodeBatch
    {
        /** @var VideoCodeBatch|null $batch */
        $batch = VideoCodeBatch::query()
            ->forCourseVideo($course->id, $video->id)
            ->open()
            ->notDeleted()
            ->first();

        return $batch;
    }

    private function recordExport(
        User $admin,
        VideoCodeBatch $batch,
        string $format,
        ?int $startSequence = null,
        ?int $endSequence = null
    ): void {
        $range = $this->resolveSequenceRange($batch, $startSequence, $endSequence);

        $this->appendExportRecord($admin, $batch, [
            'type' => 'download',
            'format' => $format,
            'delivery_channel' => 'download',
            'status' => 'completed',
            'code_range' => sprintf('%d-%d', $range['start'], $range['end']),
            'start_sequence' => $range['start'],
            'end_sequence' => $range['end'],
            'count' => $range['count'],
            'file_name' => $this->buildExportFilename($batch->loadMissing(['video']), $format),
        ]);
    }

    private function buildExportFilename(VideoCodeBatch $batch, string $extension): string
    {
        /** @var Video|null $video */
        $video = $batch->video;
        $videoTitle = $video?->translate('title') ?? 'video';
        $safeVideoTitle = Str::slug($videoTitle, '-');

        if ($safeVideoTitle === '') {
            $safeVideoTitle = 'video';
        }

        return sprintf('%s-%s.%s', $safeVideoTitle, $batch->batch_code, $extension);
    }

    /**
     * @return array{start:int,end:int,count:int}
     */
    private function resolveSequenceRange(
        VideoCodeBatch $batch,
        ?int $startSequence = null,
        ?int $endSequence = null
    ): array {
        $start = $startSequence ?? 1;
        $end = $endSequence ?? $batch->quantity;

        $start = max(1, min($start, $batch->quantity));
        $end = max($start, min($end, $batch->quantity));

        return [
            'start' => $start,
            'end' => $end,
            'count' => $end - $start + 1,
        ];
    }

    /**
     * @param  resource  $output
     */
    private function writeCsvRows($output, VideoCodeBatch $batch, ?int $startSequence = null, ?int $endSequence = null): void
    {
        fputcsv($output, ['Sequence', 'Code', 'Video', 'Course', 'View Limit']);

        /** @var Video|null $video */
        $video = $batch->video;
        /** @var Course|null $course */
        $course = $batch->course;

        $videoTitle = $video?->translate('title') ?? 'Video';
        $courseTitle = $course?->translate('title') ?? 'Course';

        foreach ($this->generateCodesForExport($batch, $startSequence, $endSequence) as $seq => $code) {
            fputcsv($output, [
                $seq,
                $code,
                $videoTitle,
                $courseTitle,
                $batch->view_limit_per_code,
            ]);
        }
    }

    private function buildCsvContent(VideoCodeBatch $batch, ?int $startSequence = null, ?int $endSequence = null): string
    {
        $output = fopen('php://temp', 'r+');
        if ($output === false) {
            throw new \RuntimeException('Unable to allocate CSV export buffer.');
        }

        $this->writeCsvRows($output, $batch, $startSequence, $endSequence);
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        if (! is_string($csv)) {
            throw new \RuntimeException('Unable to build CSV export.');
        }

        return $csv;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function appendExportRecord(User $admin, VideoCodeBatch $batch, array $record): array
    {
        /** @var array<string, mixed> $saved */
        $saved = DB::transaction(function () use ($admin, $batch, $record): array {
            /** @var VideoCodeBatch|null $lockedBatch */
            $lockedBatch = VideoCodeBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedBatch instanceof VideoCodeBatch) {
                $this->deny(ErrorCodes::VIDEO_CODE_BATCH_NOT_FOUND, 'Batch not found.', 404);
            }

            $metadata = is_array($lockedBatch->metadata) ? $lockedBatch->metadata : [];
            $exports = is_array($metadata['exports'] ?? null) ? $metadata['exports'] : [];

            $savedRecord = array_merge([
                'id' => (string) Str::uuid(),
                'type' => 'download',
                'format' => null,
                'delivery_channel' => 'download',
                'status' => 'completed',
                'exported_at' => Carbon::now()->toIso8601String(),
                'exported_by' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                ],
            ], $record);

            $exports[] = $savedRecord;
            $metadata['exports'] = $exports;
            $lockedBatch->metadata = $metadata;
            $lockedBatch->save();

            $batch->setAttribute('metadata', $lockedBatch->metadata);

            return $savedRecord;
        });

        return $saved;
    }

    /**
     * @param  array<string, mixed>  $updates
     * @return array<string, mixed>|null
     */
    private function updateExportRecord(VideoCodeBatch $batch, string $recordId, array $updates): ?array
    {
        /** @var array<string, mixed>|null $updatedRecord */
        $updatedRecord = DB::transaction(function () use ($batch, $recordId, $updates): ?array {
            /** @var VideoCodeBatch|null $lockedBatch */
            $lockedBatch = VideoCodeBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedBatch instanceof VideoCodeBatch) {
                return null;
            }

            $metadata = is_array($lockedBatch->metadata) ? $lockedBatch->metadata : [];
            $exports = is_array($metadata['exports'] ?? null) ? $metadata['exports'] : [];
            $updated = null;

            foreach ($exports as $index => $export) {
                if (! is_array($export) || (string) ($export['id'] ?? '') !== $recordId) {
                    continue;
                }

                $exports[$index] = array_merge($export, $updates);
                $updated = $exports[$index];
                break;
            }

            if ($updated === null) {
                return null;
            }

            $metadata['exports'] = $exports;
            $lockedBatch->metadata = $metadata;
            $lockedBatch->save();

            $batch->setAttribute('metadata', $lockedBatch->metadata);

            return $updated;
        });

        return $updatedRecord;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findExportRecord(VideoCodeBatch $batch, string $recordId): ?array
    {
        $metadata = is_array($batch->metadata) ? $batch->metadata : [];
        $exports = is_array($metadata['exports'] ?? null) ? $metadata['exports'] : [];

        foreach ($exports as $export) {
            if (! is_array($export) || (string) ($export['id'] ?? '') !== $recordId) {
                continue;
            }

            return $export;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeExportRecord(mixed $record): array
    {
        $data = is_array($record) ? $record : [];

        return [
            'id' => is_string($data['id'] ?? null) ? $data['id'] : null,
            'type' => $data['type'] ?? 'download',
            'format' => $data['format'] ?? null,
            'delivery_channel' => $data['delivery_channel'] ?? 'download',
            'status' => $data['status'] ?? 'completed',
            'exported_at' => $data['exported_at'] ?? null,
            'completed_at' => $data['completed_at'] ?? null,
            'exported_by' => is_array($data['exported_by'] ?? null) ? $data['exported_by'] : null,
            'destination_masked' => is_string($data['destination_masked'] ?? null) ? $data['destination_masked'] : null,
            'code_range' => $data['code_range'] ?? null,
            'start_sequence' => is_numeric($data['start_sequence'] ?? null) ? (int) $data['start_sequence'] : null,
            'end_sequence' => is_numeric($data['end_sequence'] ?? null) ? (int) $data['end_sequence'] : null,
            'count' => is_numeric($data['count'] ?? null) ? (int) $data['count'] : null,
            'file_name' => is_string($data['file_name'] ?? null) ? $data['file_name'] : null,
            'error' => is_string($data['error'] ?? null) ? $data['error'] : null,
        ];
    }

    /**
     * @param  Builder<VideoCodeRedemption>  $query
     */
    private function applyRedemptionSearch(Builder $query, VideoCodeBatch $batch, string $term): void
    {
        $matchedSequence = $this->resolveSequenceFromCode($batch, $term);

        if ($matchedSequence !== null) {
            $query->where('sequence_number', $matchedSequence);

            return;
        }

        $like = '%'.$term.'%';
        $sequence = ctype_digit($term) ? (int) $term : null;

        $query->where(function (Builder $builder) use ($like, $sequence, $term): void {
            $builder->whereHas('user', function (Builder $userQuery) use ($like, $term): void {
                $userQuery->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like);
                $this->phoneSearch->applyUserPhoneLike($userQuery, $term);
            });

            if ($sequence !== null) {
                $builder->orWhere('sequence_number', $sequence);
            }
        });
    }

    private function resolveSequenceFromCode(VideoCodeBatch $batch, string $term): ?int
    {
        $parsed = $this->codeGenerator->parseAndValidate($term);

        if ($parsed === null) {
            return null;
        }

        if ((int) $parsed['batch']->id !== (int) $batch->id) {
            return null;
        }

        return $parsed['sequence'];
    }

    private function normalizeDestination(string $destination): string
    {
        $normalized = preg_replace('/\D+/', '', $destination) ?? '';

        if (str_starts_with($normalized, '00')) {
            $normalized = substr($normalized, 2);
        }

        if ($normalized === '') {
            throw new \RuntimeException('Destination phone must contain digits.');
        }

        return $normalized;
    }

    private function maskDestination(string $destination): string
    {
        $length = strlen($destination);
        if ($length <= 4) {
            return '+'.$destination;
        }

        $prefix = substr($destination, 0, min(5, max(2, $length - 2)));
        $suffixLength = $length > 8 ? 3 : 2;
        $suffix = substr($destination, -$suffixLength);
        $maskLength = max(2, $length - strlen($prefix) - strlen($suffix));

        return '+'.$prefix.str_repeat('*', $maskLength).$suffix;
    }

    private function resolveWhatsAppSendFailureMessage(\Throwable $throwable, string $instanceName): string
    {
        $message = $throwable->getMessage();
        if (! $this->isLikelyInstanceConnectionError($message)) {
            return $message;
        }

        $instanceStateMessage = $this->resolveInstanceStateErrorMessage($instanceName);
        if ($instanceStateMessage !== null) {
            return $instanceStateMessage;
        }

        return $message;
    }

    private function isLikelyInstanceConnectionError(string $message): bool
    {
        $normalized = strtolower($message);

        return str_contains($normalized, 'onwha')
            || str_contains($normalized, 'cannot read properties of undefined')
            || str_contains($normalized, 'undefined (reading')
            || str_contains($normalized, 'log out instance')
            || str_contains($normalized, 'unauthorized');
    }

    private function resolveInstanceStateErrorMessage(string $instanceName): ?string
    {
        try {
            $instances = $this->evolutionApiClient->fetchInstances();
        } catch (\Throwable) {
            return null;
        }

        foreach ($instances as $instance) {
            if (! is_array($instance) || (string) ($instance['name'] ?? '') !== $instanceName) {
                continue;
            }

            $status = (string) ($instance['connectionStatus'] ?? 'unknown');
            if (in_array(strtolower($status), ['open', 'connected', 'online'], true)) {
                return null;
            }

            $reasonCode = $instance['disconnectionReasonCode'] ?? null;
            $disconnectedAt = (string) ($instance['disconnectionAt'] ?? '');

            $reasonPart = is_scalar($reasonCode) ? ' Reason code: '.$reasonCode.'.' : '';
            $disconnectedAtPart = $disconnectedAt !== '' ? ' Disconnected at '.$disconnectedAt.'.' : '';

            return sprintf(
                'Evolution instance "%s" is not connected (status: %s). Reconnect the instance in Evolution Manager and retry.%s%s',
                $instanceName,
                $status !== '' ? $status : 'unknown',
                $reasonPart,
                $disconnectedAtPart
            );
        }

        return sprintf(
            'Evolution instance "%s" is not connected. Reconnect the instance in Evolution Manager and retry.',
            $instanceName
        );
    }

    private function createQrWriter(int $size): Writer
    {
        return new Writer(new GDLibRenderer(size: $size, margin: 2, imageFormat: 'png', compressionQuality: 4));
    }

    private function generateQrCodeDataUrl(Writer $writer, string $code): string
    {
        $png = $writer->writeString($code);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    /**
     * @return array{cards_per_page:int,card_height_mm:int,qr_display_mm:int,qr_size_px:int,code_font_px:int,meta_font_px:int}
     */
    private function resolvePdfLayout(int $cardsPerPage): array
    {
        return self::PDF_LAYOUTS[$cardsPerPage]
            ?? throw new DomainException('cards_per_page must be one of 4, 6, or 8.', ErrorCodes::INVALID_STATE, 422);
    }

    private function assertSoldLimitIsValid(VideoCodeBatch $batch, int $soldLimit): void
    {
        if ($soldLimit > $batch->quantity) {
            $this->deny(ErrorCodes::INVALID_STATE, 'Sold limit cannot exceed batch quantity.', 422);
        }

        if ($soldLimit < $batch->redeemed_count) {
            $this->deny(
                ErrorCodes::INVALID_STATE,
                sprintf('Sold limit cannot be less than already redeemed codes (%d).', $batch->redeemed_count),
                422
            );
        }
    }

    private function assertCourseSupportsVideoCodes(Course $course): void
    {
        if (! $course->usesVideoCodeAccess()) {
            $this->deny(
                ErrorCodes::INVALID_STATE,
                'Video code batches can only be created for courses using the video_code access model.',
                422
            );
        }
    }

    private function assertVideoBelongsToCourse(Video $video, Course $course): void
    {
        $videoExistsInCourse = $course->videos()
            ->whereKey($video->id)
            ->exists();

        if (! $videoExistsInCourse) {
            $this->deny(ErrorCodes::NOT_FOUND, 'Video not found in this course.', 404);
        }
    }

    /**
     * @return never
     */
    private function deny(string $code, string $message, int $status): void
    {
        throw new DomainException($message, $code, $status);
    }
}
