<?php

declare(strict_types=1);

namespace App\Services\VideoAccess;

use App\Enums\BulkItemStatus;
use App\Enums\BulkJobStatus;
use App\Enums\WhatsAppCodeFormat;
use App\Exceptions\DomainException;
use App\Jobs\ProcessBulkWhatsAppJob;
use App\Models\BulkWhatsAppJob;
use App\Models\BulkWhatsAppJobItem;
use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\User;
use App\Models\VideoAccessCode;
use App\Services\Centers\CenterScopeService;
use App\Services\VideoAccess\Contracts\BulkWhatsAppServiceInterface;
use App\Support\ErrorCodes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BulkWhatsAppService implements BulkWhatsAppServiceInterface
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    public function initiate(User $admin, int $centerId, array $codeIds, WhatsAppCodeFormat $format): BulkWhatsAppJob
    {
        $this->assertAdminScope($admin, $centerId);

        $uniqueIds = array_values(array_unique(array_filter(array_map('intval', $codeIds), static fn (int $id): bool => $id > 0)));

        if ($uniqueIds === []) {
            $this->deny(ErrorCodes::NOT_FOUND, 'No code IDs were provided.', 422);
        }

        /** @var Center|null $center */
        $center = Center::query()->find($centerId);
        if (! $center instanceof Center) {
            $this->deny(ErrorCodes::NOT_FOUND, 'Center not found.', 404);
        }

        $availableCodeIds = VideoAccessCode::query()
            ->whereIn('id', $uniqueIds)
            ->where('center_id', $centerId)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($availableCodeIds === []) {
            $this->deny(ErrorCodes::NOT_FOUND, 'No matching codes found for this center.', 404);
        }

        return DB::transaction(function () use ($admin, $center, $availableCodeIds, $format): BulkWhatsAppJob {
            /** @var BulkWhatsAppJob $job */
            $job = BulkWhatsAppJob::query()->create([
                'center_id' => $center->id,
                'total_codes' => count($availableCodeIds),
                'sent_count' => 0,
                'failed_count' => 0,
                'status' => BulkJobStatus::Pending,
                'format' => $format,
                'created_by' => $admin->id,
                'settings' => $this->resolveSettings($center),
            ]);

            foreach ($availableCodeIds as $codeId) {
                BulkWhatsAppJobItem::query()->create([
                    'bulk_job_id' => $job->id,
                    'video_access_code_id' => $codeId,
                    'status' => BulkItemStatus::Pending,
                    'attempts' => 0,
                ]);
            }

            ProcessBulkWhatsAppJob::dispatch((int) $job->id);

            return $job->fresh(['creator']) ?? $job;
        });
    }

    public function pause(User $admin, BulkWhatsAppJob $job): BulkWhatsAppJob
    {
        $this->assertAdminScope($admin, (int) $job->center_id);

        if ($job->status === BulkJobStatus::Completed || $job->status === BulkJobStatus::Cancelled) {
            return $job;
        }

        $job->status = BulkJobStatus::Paused;
        $job->save();

        return $job->fresh(['creator']) ?? $job;
    }

    public function resume(User $admin, BulkWhatsAppJob $job): BulkWhatsAppJob
    {
        $this->assertAdminScope($admin, (int) $job->center_id);

        if (! in_array($job->status, [BulkJobStatus::Paused, BulkJobStatus::Failed, BulkJobStatus::Pending], true)) {
            return $job;
        }

        $job->status = BulkJobStatus::Processing;
        if ($job->started_at === null) {
            $job->started_at = Carbon::now();
        }

        $job->save();

        ProcessBulkWhatsAppJob::dispatch((int) $job->id);

        return $job->fresh(['creator']) ?? $job;
    }

    public function retryFailed(User $admin, BulkWhatsAppJob $job): BulkWhatsAppJob
    {
        $this->assertAdminScope($admin, (int) $job->center_id);

        DB::transaction(function () use ($job): void {
            $job->items()
                ->failed()
                ->update([
                    'status' => BulkItemStatus::Pending,
                    'attempts' => 0,
                    'error' => null,
                ]);

            $job->failed_count = 0;
            $job->status = BulkJobStatus::Processing;
            if ($job->started_at === null) {
                $job->started_at = Carbon::now();
            }

            $job->completed_at = null;
            $job->save();
        });

        ProcessBulkWhatsAppJob::dispatch((int) $job->id);

        return $job->fresh(['creator']) ?? $job;
    }

    public function cancel(User $admin, BulkWhatsAppJob $job): BulkWhatsAppJob
    {
        $this->assertAdminScope($admin, (int) $job->center_id);

        if ($job->status === BulkJobStatus::Completed) {
            return $job;
        }

        $job->status = BulkJobStatus::Cancelled;
        $job->completed_at = Carbon::now();
        $job->save();

        return $job->fresh(['creator']) ?? $job;
    }

    private function assertAdminScope(User $admin, int $centerId): void
    {
        if ($admin->is_student) {
            $this->deny(ErrorCodes::UNAUTHORIZED, 'Only admins can perform this action.', 403);
        }

        $this->centerScopeService->assertAdminCenterId($admin, $centerId);
    }

    /**
     * @return array<string, int>
     */
    private function resolveSettings(Center $center): array
    {
        /** @var CenterSetting|null $setting */
        $setting = $center->setting()->first();
        $settings = $setting instanceof CenterSetting && is_array($setting->settings) ? $setting->settings : [];
        $bulkSettings = is_array($settings['whatsapp_bulk_settings'] ?? null) ? $settings['whatsapp_bulk_settings'] : [];

        return [
            'delay_seconds' => is_numeric($bulkSettings['delay_seconds'] ?? null) ? max(0, (int) $bulkSettings['delay_seconds']) : 3,
            'batch_size' => is_numeric($bulkSettings['batch_size'] ?? null) ? max(1, (int) $bulkSettings['batch_size']) : 50,
            'batch_pause_seconds' => is_numeric($bulkSettings['batch_pause_seconds'] ?? null) ? max(0, (int) $bulkSettings['batch_pause_seconds']) : 60,
            'max_retries' => is_numeric($bulkSettings['max_retries'] ?? null) ? max(0, (int) $bulkSettings['max_retries']) : 2,
            'max_failures_before_pause' => is_numeric($bulkSettings['max_failures_before_pause'] ?? null) ? max(1, (int) $bulkSettings['max_failures_before_pause']) : 10,
        ];
    }

    /**
     * @return never
     */
    private function deny(string $code, string $message, int $status): void
    {
        throw new DomainException($message, $code, $status);
    }
}
