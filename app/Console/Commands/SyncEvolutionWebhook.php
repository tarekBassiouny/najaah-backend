<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Evolution\EvolutionApiClient;
use Illuminate\Console\Command;

class SyncEvolutionWebhook extends Command
{
    protected $signature = 'evolution:webhook:sync {instanceName : Evolution instance name} {--disable : Disable the webhook instead of enabling it}';

    protected $description = 'Configure the Evolution instance webhook to call this backend.';

    public function __construct(private readonly EvolutionApiClient $client)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $instanceName = (string) $this->argument('instanceName');
        $enabled = ! (bool) $this->option('disable');
        $url = (string) config('evolution.webhook_url', '');

        if ($url === '') {
            $this->error('EVOLUTION_WEBHOOK_URL is missing.');

            return self::FAILURE;
        }

        $headers = [];
        $secret = (string) config('evolution.webhook_secret', '');
        $secretHeader = (string) config('evolution.webhook_secret_header', 'x-evolution-secret');

        if ($secret !== '') {
            $headers[$secretHeader] = $secret;
        }

        $payload = [
            'enabled' => $enabled,
            'url' => $url,
            'headers' => $headers,
            'byEvents' => false,
            'base64' => false,
            'events' => $enabled ? config('evolution.webhook_events', []) : [],
        ];

        $response = $this->client->setWebhook($instanceName, $payload);
        $current = $this->client->findWebhook($instanceName);

        $this->info(sprintf(
            'Webhook %s for instance [%s].',
            $enabled ? 'configured' : 'disabled',
            $instanceName
        ));

        $this->line(json_encode([
            'set' => $response,
            'current' => $current,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
