<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Center;
use App\Services\Logging\LogContextResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCenterLogoJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $centerId, public readonly string $logoUrl) {}

    public function handle(): void
    {
        $center = Center::find($this->centerId);

        if (! $center instanceof Center) {
            Log::warning('Center logo processing skipped due to missing center.', $this->resolveLogContext([
                'source' => 'job',
                'center_id' => $this->centerId,
            ]));

            return;
        }

        if ($center->logo_url === null || $center->logo_url === '') {
            return;
        }

        if ($center->logo_url !== $this->logoUrl) {
            return;
        }

        $metadata = is_array($center->branding_metadata) ? $center->branding_metadata : [];
        $processedAt = $metadata['logo_processed_at'] ?? null;
        $source = $metadata['logo_source'] ?? null;

        if ($source === $this->logoUrl && is_string($processedAt) && $processedAt !== '') {
            return;
        }

        $metadata['logo_source'] = $this->logoUrl;
        $metadata['logo_processed_at'] = now()->toISOString();

        $center->branding_metadata = $metadata;
        $center->save();
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Center logo processing failed.', $this->resolveLogContext([
            'source' => 'job',
            'center_id' => $this->centerId,
            'error' => $exception->getMessage(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function resolveLogContext(array $overrides = []): array
    {
        return app(LogContextResolver::class)->resolve($overrides);
    }
}
