<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

uses()->group('admin', 'localization', 'middleware');

beforeEach(function (): void {
    Route::middleware(['api', 'normalize.admin.api'])
        ->get('/api/v1/admin/__test/localization/success', static fn () => response()->json([
            'data' => ['ok' => true],
        ]));

    Route::middleware(['api', 'normalize.admin.api'])
        ->get('/api/v1/admin/__test/localization/not-found', static fn () => response()->json([], 404));
});

it('returns localized normalized success default message for arabic locale', function (): void {
    $response = $this->withHeader('X-Locale', 'ar')
        ->getJson('/api/v1/admin/__test/localization/success', [
            'X-Api-Key' => (string) config('services.system_api_key'),
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'تم تنفيذ الطلب بنجاح.');
});

it('returns localized normalized error default message for arabic locale', function (): void {
    $response = $this->withHeader('X-Locale', 'ar')
        ->getJson('/api/v1/admin/__test/localization/not-found', [
            'X-Api-Key' => (string) config('services.system_api_key'),
        ]);

    $response->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'المورد غير موجود.')
        ->assertJsonPath('error.message', 'المورد غير موجود.');
});

it('keeps english default message when locale header is not provided', function (): void {
    $response = $this->getJson('/api/v1/admin/__test/localization/success', [
        'X-Api-Key' => (string) config('services.system_api_key'),
    ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Request completed successfully.');
});
