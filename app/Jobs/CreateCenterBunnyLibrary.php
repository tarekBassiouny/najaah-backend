<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Center;
use App\Services\Bunny\BunnyLibraryService;
use App\Services\Logging\LogContextResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateCenterBunnyLibrary implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly int $centerId
    ) {}

    public function handle(BunnyLibraryService $libraryService): void
    {
        $center = Center::find($this->centerId);

        if (! $center instanceof Center) {
            Log::warning('Center Bunny library creation skipped due to missing center.', $this->resolveLogContext([
                'source' => 'job',
                'center_id' => $this->centerId,
            ]));

            return;
        }

        if (is_numeric($center->bunny_library_id)) {
            return;
        }

        $env = (string) config('app.env', 'local');
        $libraryName = sprintf('%s-%d-%s', $center->slug, $center->id, $env);
        $created = $libraryService->createLibrary($libraryName);

        $center->bunny_library_id = $created['id'];
        $center->save();
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Center Bunny library creation failed.', $this->resolveLogContext([
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
