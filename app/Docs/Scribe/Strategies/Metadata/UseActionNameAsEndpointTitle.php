<?php

declare(strict_types=1);

namespace App\Docs\Scribe\Strategies\Metadata;

use Illuminate\Support\Str;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\Strategy;

class UseActionNameAsEndpointTitle extends Strategy
{
    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, string>|null
     */
    public function __invoke(ExtractedEndpointData $endpointData, array $settings = []): ?array
    {
        $uri = ltrim($endpointData->uri, '/');
        if (! str_starts_with($uri, 'api/v1/')) {
            return null;
        }

        if (str_starts_with($uri, 'api/v1/admin/')) {
            return null;
        }

        $currentTitle = trim($endpointData->metadata->title ?? '');
        if ($currentTitle !== '') {
            return null;
        }

        $action = $endpointData->method->getName();
        if ($action === '__invoke') {
            $controller = class_basename($endpointData->controller->getName());
            $action = preg_replace('/Controller$/', '', $controller) ?? $controller;
        }

        $title = Str::of($action)
            ->snake()
            ->replace('_', ' ')
            ->title()
            ->replace('Whatsapp', 'WhatsApp')
            ->replace('Api', 'API')
            ->toString();

        return ['title' => $title];
    }
}
