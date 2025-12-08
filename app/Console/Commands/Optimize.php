<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class Optimize extends Command
{
    protected $signature = 'system:optimize';

    protected $description = 'Run common maintenance tasks and builds';

    public function handle(): int
    {
        $this->runArtisan('config:clear');
        $this->runArtisan('cache:clear');
        $this->runArtisan('route:clear');
        $this->runArtisan('view:clear');
        $this->runArtisan('optimize:clear');
        $this->runArtisan('migrate', ['--force' => true]);
        $this->shell(['composer', 'dump-autoload']);
        $this->runArtisan('scribe:generate', ['--force' => true]);

        return self::SUCCESS;
    }

    /**
     * Run an Artisan command.
     *
     * @param  array<string, mixed>  $options
     */
    private function runArtisan(string $command, array $options = []): void
    {
        $this->line("Artisan: {$command}");
        $code = $this->call($command, $options);

        if ($code !== self::SUCCESS) {
            $this->error("Command failed: {$command}");
            exit(self::FAILURE);
        }
    }

    /**
     * Run a shell command and stream output.
     *
     * @param  array<int, string>  $command
     */
    private function shell(array $command): void
    {
        $process = new Process($command);
        $process->setTimeout(null);
        $process->run(function ($type, $buffer): void {
            $this->output->write($buffer);
        });

        if (! $process->isSuccessful()) {
            $this->error('Command failed: '.implode(' ', $command));
            exit(self::FAILURE);
        }
    }
}
