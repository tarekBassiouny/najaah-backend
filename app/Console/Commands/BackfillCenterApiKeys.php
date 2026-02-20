<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Center;
use Illuminate\Console\Command;

class BackfillCenterApiKeys extends Command
{
    protected $signature = 'centers:backfill-api-keys {--dry-run : Show how many centers are missing keys without updating}';

    protected $description = 'Generate API keys for centers that do not have one.';

    public function handle(): int
    {
        $query = Center::query()->where(function ($builder): void {
            $builder->whereNull('api_key')->orWhere('api_key', '');
        });

        $missingCount = (int) $query->count();

        if ($missingCount === 0) {
            $this->info('All centers already have API keys.');

            return self::SUCCESS;
        }

        if ((bool) $this->option('dry-run')) {
            $this->info("Centers missing API keys: {$missingCount}");

            return self::SUCCESS;
        }

        $this->info("Backfilling API keys for {$missingCount} centers...");
        $updated = 0;

        Center::query()
            ->where(function ($builder): void {
                $builder->whereNull('api_key')->orWhere('api_key', '');
            })
            ->orderBy('id')
            ->chunkById(100, function ($centers) use (&$updated): void {
                foreach ($centers as $center) {
                    $center->forceFill([
                        'api_key' => Center::generateUniqueApiKey(),
                    ])->save();
                    $updated++;
                }
            });

        $this->info("Backfilled {$updated} center API keys.");

        return self::SUCCESS;
    }
}
