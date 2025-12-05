<?php

declare(strict_types=1);

namespace App\Console\Commands\CodeQuality;

use Illuminate\Console\Command;

class RunCodeQuality extends Command
{
    protected $signature = 'system:check';
    protected $description = 'Run PHPStan, Pint, PHPCS, and tests in one command';

    public function handle(): int
    {
        $this->line("\nRunning code quality checks...\n");

        $this->shell("vendor/bin/pint --test");
        $this->shell("vendor/bin/phpstan analyse");
        $this->shell("vendor/bin/phpcs --standard=phpcs.xml");
        $this->shell("php artisan test");

        $this->info("\n✔ All checks completed.");
        return self::SUCCESS;
    }

    private function shell(string $command): void
    {
        $this->info("→ {$command}");
        exec($command, $output);
        $this->line(implode("\n", $output));
    }
}
