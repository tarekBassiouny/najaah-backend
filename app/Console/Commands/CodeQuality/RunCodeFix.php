<?php

declare(strict_types=1);

namespace App\Console\Commands\CodeQuality;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class RunCodeFix extends Command
{
    protected $signature = 'system:fix';

    protected $description = 'Run Pint (fix), PHPCBF, PHPStan, and tests';

    public function handle(): int
    {
        $this->shell(['./vendor/bin/pint']);
        $this->shell(['./vendor/bin/phpcbf', '--standard=phpcs.xml']);
        $this->shell(['./vendor/bin/phpstan', 'analyse'], $this->phpstanEnv());
        $this->shell(['php', 'artisan', 'test']);

        return self::SUCCESS;
    }

    /**
     * Run a shell command and stream output.
     *
     * @param  array<int, string>  $command
     * @param  array<string, string>  $env
     */
    private function shell(array $command, array $env = []): void
    {
        $process = new Process($command, base_path(), $env === [] ? null : $env);
        $process->setTimeout(null);
        $process->run(function ($type, $buffer): void {
            $this->output->write($buffer);
        });

        if (! $process->isSuccessful()) {
            $this->error('Command failed: '.implode(' ', $command));
            exit(self::FAILURE);
        }
    }

    /**
     * @return array<string, string>
     */
    private function phpstanEnv(): array
    {
        $tmpDir = storage_path('framework/cache/phpstan');

        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        return [
            'PHPSTAN_TMPDIR' => $tmpDir,
            'TMPDIR' => $tmpDir,
        ];
    }
}
