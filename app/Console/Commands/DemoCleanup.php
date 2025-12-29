<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Center;
use App\Models\Pdf;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DemoCleanup extends Command
{
    protected $signature = 'demo:cleanup';

    protected $description = 'Remove demo data created by ProductionDemoSeeder.';

    public function handle(): int
    {
        try {
            $this->guardEnvironment();
        } catch (RuntimeException $runtimeException) {
            $this->error($runtimeException->getMessage());

            return self::FAILURE;
        }

        $centerCount = Center::withTrashed()->where('is_demo', true)->count();
        $videoCount = Video::withTrashed()->where('is_demo', true)->count();
        $pdfCount = Pdf::withTrashed()->where('is_demo', true)->count();

        Video::withTrashed()->where('is_demo', true)->forceDelete();
        Pdf::withTrashed()->where('is_demo', true)->forceDelete();
        Center::withTrashed()->where('is_demo', true)->forceDelete();

        Log::info('Production demo cleanup completed.', [
            'centers_removed' => $centerCount,
            'videos_removed' => $videoCount,
            'pdfs_removed' => $pdfCount,
        ]);

        $this->info('Demo cleanup completed.');

        return self::SUCCESS;
    }

    private function guardEnvironment(): void
    {
        if (! app()->environment('production')) {
            throw new RuntimeException('Demo cleanup can only run in production.');
        }

        if (! config('demo.enabled')) {
            throw new RuntimeException('Demo cleanup is disabled.');
        }
    }
}
