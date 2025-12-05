<?php

declare(strict_types=1);

namespace App\Console\Commands\CodeQuality;

use Illuminate\Console\Command;

class RunCodeFix extends Command
{
    protected $signature = 'system:fix';
    protected $description = 'Auto-fix code style issues using Pint and PHPCBF, then run checks';

    public function handle(): int
    {
        $this->line("\nRunning auto-fix tools...\n");

        $this->shell("vendor/bin/pint");
        $this->shell("vendor/bin/phpcbf --standard=phpcs.xml");
        $this->shell("vendor/bin/phpstan analyse");
        $this->shell("php artisan test");

        $this->info("\n✔ Fix completed and checks executed.");
        return self::SUCCESS;
    }

    private function shell(string $command): void
    {
        $this->info("→ {$command}");
        exec($command, $output);
        $this->line(implode("\n", $output));
    }
}
