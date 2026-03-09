<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

uses()->group('localization', 'middleware');

beforeEach(function (): void {
    Route::middleware('api')
        ->get('/api/v1/__test/locale/echo', static function () {
            return response()->json([
                'app_locale' => app()->getLocale(),
                'request_locale' => request()->attributes->get('locale'),
            ]);
        });
});

it('prioritizes x-locale over accept-language', function (): void {
    $response = $this
        ->withHeader('X-Locale', 'ar')
        ->withHeader('Accept-Language', 'en-US,en;q=0.9')
        ->getJson('/api/v1/__test/locale/echo', [
            'X-Api-Key' => (string) config('services.system_api_key'),
        ]);

    $response->assertOk()
        ->assertJsonPath('app_locale', 'ar')
        ->assertJsonPath('request_locale', 'ar');
});

it('normalizes regional accept-language locale values', function (): void {
    $response = $this
        ->withHeader('Accept-Language', 'ar-EG,ar;q=0.9,en;q=0.8')
        ->getJson('/api/v1/__test/locale/echo', [
            'X-Api-Key' => (string) config('services.system_api_key'),
        ]);

    $response->assertOk()
        ->assertJsonPath('app_locale', 'ar')
        ->assertJsonPath('request_locale', 'ar');
});

it('falls back to app locale when headers are unsupported', function (): void {
    config(['app.locale' => 'en']);

    $response = $this
        ->withHeader('X-Locale', 'fr')
        ->withHeader('Accept-Language', 'de-DE,de;q=0.9')
        ->getJson('/api/v1/__test/locale/echo', [
            'X-Api-Key' => (string) config('services.system_api_key'),
        ]);

    $response->assertOk()
        ->assertJsonPath('app_locale', 'en')
        ->assertJsonPath('request_locale', 'en');
});
