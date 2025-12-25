<?php

declare(strict_types=1);

use App\Http\Middleware\ResolveCenterApiKey;
use App\Models\Center;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('middleware', 'core');

test('resolves system api key', function (): void {
    config(['services.system_api_key' => 'system-key']);

    $middleware = new ResolveCenterApiKey;
    $request = Request::create('/api/v1/auth/send-otp', 'POST');
    $request->headers->set('X-Api-Key', 'system-key');

    $response = $middleware->handle($request, function (Request $request) {
        return response()->json([
            'center_id' => $request->attributes->get('resolved_center_id'),
        ]);
    });

    TestResponse::fromBaseResponse($response)
        ->assertOk()
        ->assertJson(['center_id' => null]);
});

test('resolves center api key', function (): void {
    $center = Center::factory()->create(['api_key' => 'center-key']);

    $middleware = new ResolveCenterApiKey;
    $request = Request::create('/api/v1/auth/send-otp', 'POST');
    $request->headers->set('X-Api-Key', 'center-key');

    $response = $middleware->handle($request, function (Request $request) {
        return response()->json([
            'center_id' => $request->attributes->get('resolved_center_id'),
        ]);
    });

    TestResponse::fromBaseResponse($response)
        ->assertOk()
        ->assertJson(['center_id' => $center->id]);
});

test('rejects invalid api key', function (): void {
    $middleware = new ResolveCenterApiKey;
    $request = Request::create('/api/v1/auth/send-otp', 'POST');
    $request->headers->set('X-Api-Key', 'invalid-key');

    $response = $middleware->handle($request, fn () => response()->noContent());

    TestResponse::fromBaseResponse($response)
        ->assertStatus(401)
        ->assertJsonPath('error.code', 'INVALID_API_KEY');
});
