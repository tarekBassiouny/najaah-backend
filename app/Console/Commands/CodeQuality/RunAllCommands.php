<?php

declare(strict_types=1);

namespace App\Console\Commands\CodeQuality;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunAllCommands extends Command
{
    protected $signature = 'system:run-all';
    protected $description = 'Run common Laravel maintenance commands';

    public function handle(): int
    {
        $this->line("\nRunning all commands...\n");

        $this->run("config:clear");
        $this->run("cache:clear");
        $this->run("route:clear");
        $this->run("view:clear");
        $this->run("optimize:clear");
        $this->run("optimize");
        $this->run("migrate", ['--force' => true]);
        $this->run("queue:restart");

        $this->shell("composer dump-autoload -o");
        $this->shell("npm run build");

        $this->info("\n✔ All tasks completed.");
        return self::SUCCESS;
    }

    private function run(string $command, array $params = []): void
    {
        $this->info("→ {$command}");
        Artisan::call($command, $params);
        $this->output->write(Artisan::output());
    }

    private function shell(string $command): void
    {
        $this->info("→ {$command}");
        exec($command, $output);
        $this->line(implode("\n", $output));
    }
}
