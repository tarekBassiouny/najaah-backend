<?php

declare(strict_types=1);

use App\Services\VideoAccess\Contracts\VideoApprovalCodeServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class)->group('video-access', 'services');

it('generates a png qr data url for a video access code', function (): void {
    $code = \App\Models\VideoAccessCode::factory()->create([
        'code' => 'A1B2C3D4',
    ]);

    /** @var VideoApprovalCodeServiceInterface $service */
    $service = app(VideoApprovalCodeServiceInterface::class);

    $dataUrl = $service->getQrCodeDataUrl($code);

    expect($dataUrl)->toStartWith('data:image/png;base64,');

    $payload = substr($dataUrl, strlen('data:image/png;base64,'));
    $binary = base64_decode($payload, true);

    expect($binary)->not->toBeFalse();
    expect((string) $binary)->toStartWith("\x89PNG");
});
