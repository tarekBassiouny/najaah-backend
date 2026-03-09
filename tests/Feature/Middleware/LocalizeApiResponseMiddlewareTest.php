<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

uses()->group('localization', 'middleware');

beforeEach(function (): void {
    Route::middleware('api')
        ->get('/api/v1/__test/localization/message', static fn () => response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => [],
        ]));

    Route::middleware('api')
        ->get('/api/v1/__test/localization/error', static fn () => response()->json([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED',
                'message' => 'Authentication required.',
            ],
        ], 401));

    Route::middleware('api')
        ->post('/api/v1/__test/localization/validation', static function () {
            $validator = Validator::make(request()->all(), [
                'code' => ['required', 'string', 'min:4'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Validation failed',
                        'details' => $validator->errors()->toArray(),
                    ],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Request completed successfully.',
            ]);
        });
});

it('localizes top-level message for arabic locale', function (): void {
    $response = $this->withHeader('X-Locale', 'ar')
        ->getJson('/api/v1/__test/localization/message', [
            'X-Api-Key' => (string) config('services.system_api_key'),
        ]);

    $response->assertOk()
        ->assertJsonPath('message', 'تمت العملية بنجاح.');
});

it('localizes nested error message for arabic locale', function (): void {
    $response = $this->withHeader('X-Locale', 'ar')
        ->getJson('/api/v1/__test/localization/error', [
            'X-Api-Key' => (string) config('services.system_api_key'),
        ]);

    $response->assertStatus(401)
        ->assertJsonPath('error.message', 'المصادقة مطلوبة.');
});

it('keeps english messages when locale is english', function (): void {
    $response = $this->withHeader('X-Locale', 'en')
        ->getJson('/api/v1/__test/localization/message', [
            'X-Api-Key' => (string) config('services.system_api_key'),
        ]);

    $response->assertOk()
        ->assertJsonPath('message', 'Operation completed');
});

it('localizes validation message and details for arabic locale', function (): void {
    $response = $this->withHeader('X-Locale', 'ar')
        ->postJson('/api/v1/__test/localization/validation', [], [
            'X-Api-Key' => (string) config('services.system_api_key'),
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.message', 'فشل التحقق من صحة البيانات.')
        ->assertJsonPath('error.details.code.0', 'حقل code مطلوب.');
});
