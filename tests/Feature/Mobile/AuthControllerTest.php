<?php

declare(strict_types=1);

use App\Actions\Mobile\LoginAction;
use App\Models\Center;
use App\Models\JwtToken;
use App\Models\OtpCode;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Auth\Contracts\JwtServiceInterface;
use App\Services\Auth\Contracts\OtpServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('auth', 'mobile');

beforeEach(function (): void {
    config(['services.system_api_key' => 'system-key']);
});

test('send otp endpoint creates record', function (): void {
    config([
        'whatsapp.access_token' => 'test-token',
        'whatsapp.phone_number_id' => '12345',
        'whatsapp.api_version' => 'v19.0',
        'whatsapp.otp_template' => 'otp_auth',
    ]);

    Http::fake([
        'https://graph.facebook.com/v19.0/12345/messages' => Http::response([
            'messages' => [['id' => 'msg-1']],
        ], 200),
    ]);

    User::factory()->create([
        'phone' => '1234567890',
        'country_code' => '+20',
    ]);

    $response = $this->postJson('/api/v1/auth/send-otp', [
        'phone' => '1234567890',
        'country_code' => '+20',
    ], [
        'X-Api-Key' => 'system-key',
    ]);

    $response->assertOk();
    assertDatabaseHas('otp_codes', [
        'phone' => '1234567890',
    ]);
});

test('send returns token', function (): void {
    User::factory()->create([
        'phone' => '1234567890',
        'country_code' => '+20',
    ]);

    /** @var MockInterface&OtpServiceInterface $otp */
    $otp = Mockery::mock(OtpServiceInterface::class);
    $otp->allows()
        ->send('1234567890', '+20', null)
        ->andReturn('abc');

    $this->app->instance(OtpServiceInterface::class, $otp);

    $response = $this->postJson('/api/v1/auth/send-otp', [
        'phone' => '1234567890',
        'country_code' => '+20',
    ], [
        'X-Api-Key' => 'system-key',
    ]);

    $response->assertOk()->assertJson([
        'success' => true,
        'token' => 'abc',
    ]);
});

test('send rejects invalid api key', function (): void {
    $response = $this->postJson('/api/v1/auth/send-otp', [
        'phone' => '1234567890',
        'country_code' => '+20',
    ], [
        'X-Api-Key' => 'invalid-key',
    ]);

    $response->assertStatus(401)
        ->assertJsonPath('error.code', 'INVALID_API_KEY');
});

test('send rejects inactive center api key', function (): void {
    Center::factory()->create([
        'api_key' => 'inactive-center-key',
        'status' => Center::STATUS_INACTIVE,
    ]);

    $response = $this->postJson('/api/v1/auth/send-otp', [
        'phone' => '1234567890',
        'country_code' => '+20',
    ], [
        'X-Api-Key' => 'inactive-center-key',
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_INACTIVE');

    $this->assertDatabaseMissing('otp_codes', [
        'phone' => '1234567890',
        'country_code' => '+20',
    ]);
});

test('send validates base phone and country code formats', function (): void {
    $response = $this->postJson('/api/v1/auth/send-otp', [
        'phone' => '01225291841',
        'country_code' => '20',
    ], [
        'X-Api-Key' => 'system-key',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['phone', 'country_code']);

    $responseWithCountryCodeInPhone = $this->postJson('/api/v1/auth/send-otp', [
        'phone' => '201225291841',
        'country_code' => '+20',
    ], [
        'X-Api-Key' => 'system-key',
    ]);

    $responseWithCountryCodeInPhone->assertStatus(422)
        ->assertJsonValidationErrors(['phone']);
});

test('verify otp issues tokens', function (): void {
    /** @var User $user */
    $user = User::factory()->create(['phone' => '1234567890', 'country_code' => '+20', 'center_id' => null]);
    OtpCode::factory()->create([
        'user_id' => $user->id,
        'phone' => '1234567890',
        'country_code' => '+20',
        'otp_code' => '123456',
        'otp_token' => 'token123',
    ]);
    $device = UserDevice::factory()->create(['user_id' => $user->id]);
    JwtToken::factory()->create(['user_id' => $user->id]);

    $response = $this->postJson('/api/v1/auth/verify', [
        'otp' => '123456',
        'token' => 'token123',
        'device_uuid' => $device->device_id,
    ], [
        'X-Api-Key' => 'system-key',
    ]);

    $response->assertOk()->assertJsonStructure([
        'success',
        'data',
        'token' => ['access_token', 'refresh_token', 'expires_in'],
    ]);

    $user->refresh();
    expect($user->last_login_at)->not->toBeNull();
});

test('verify rejects center mismatch', function (): void {
    $centerA = Center::factory()->create(['api_key' => 'center-a-key']);
    $centerB = Center::factory()->create(['api_key' => 'center-b-key']);

    /** @var User $user */
    $user = User::factory()->create([
        'phone' => '1112223333',
        'country_code' => '+20',
        'center_id' => $centerA->id,
    ]);

    OtpCode::factory()->create([
        'user_id' => $user->id,
        'phone' => '1112223333',
        'country_code' => '+20',
        'otp_code' => '123456',
        'otp_token' => 'token-center-mismatch',
    ]);

    $response = $this->postJson('/api/v1/auth/verify', [
        'otp' => '123456',
        'token' => 'token-center-mismatch',
        'device_uuid' => 'device-1',
    ], [
        'X-Api-Key' => $centerB->api_key,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

test('verify rejects inactive center api key', function (): void {
    $center = Center::factory()->create([
        'api_key' => 'inactive-center-key',
        'status' => Center::STATUS_INACTIVE,
    ]);

    /** @var User $user */
    $user = User::factory()->create([
        'phone' => '1112223333',
        'country_code' => '+20',
        'center_id' => $center->id,
    ]);

    OtpCode::factory()->create([
        'user_id' => $user->id,
        'phone' => '1112223333',
        'country_code' => '+20',
        'otp_code' => '123456',
        'otp_token' => 'token-inactive-center',
    ]);

    $response = $this->postJson('/api/v1/auth/verify', [
        'otp' => '123456',
        'token' => 'token-inactive-center',
        'device_uuid' => 'device-1',
    ], [
        'X-Api-Key' => $center->api_key,
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_INACTIVE');
});

test('verify rejects branded student when using system api key', function (): void {
    $center = Center::factory()->create(['api_key' => 'center-a-key']);

    /** @var User $user */
    $user = User::factory()->create([
        'phone' => '1112223333',
        'country_code' => '+20',
        'center_id' => $center->id,
    ]);

    OtpCode::factory()->create([
        'user_id' => $user->id,
        'phone' => '1112223333',
        'country_code' => '+20',
        'otp_code' => '123456',
        'otp_token' => 'token-system-scope-mismatch',
    ]);

    $response = $this->postJson('/api/v1/auth/verify', [
        'otp' => '123456',
        'token' => 'token-system-scope-mismatch',
        'device_uuid' => 'device-1',
    ], [
        'X-Api-Key' => 'system-key',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

test('verify rejects system student when using center api key', function (): void {
    $center = Center::factory()->create(['api_key' => 'center-a-key']);

    /** @var User $user */
    $user = User::factory()->create([
        'phone' => '1112223333',
        'country_code' => '+20',
        'center_id' => null,
    ]);

    OtpCode::factory()->create([
        'user_id' => $user->id,
        'phone' => '1112223333',
        'country_code' => '+20',
        'otp_code' => '123456',
        'otp_token' => 'token-center-scope-mismatch',
    ]);

    $response = $this->postJson('/api/v1/auth/verify', [
        'otp' => '123456',
        'token' => 'token-center-scope-mismatch',
        'device_uuid' => 'device-1',
    ], [
        'X-Api-Key' => $center->api_key,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

test('verify binds new user to center when using center api key', function (): void {
    $center = Center::factory()->create(['api_key' => 'center-key']);

    OtpCode::factory()->create([
        'user_id' => null,
        'phone' => '7778889999',
        'country_code' => '+20',
        'otp_code' => '123456',
        'otp_token' => 'token-new-center',
    ]);

    $response = $this->postJson('/api/v1/auth/verify', [
        'otp' => '123456',
        'token' => 'token-new-center',
        'device_uuid' => 'device-center',
    ], [
        'X-Api-Key' => $center->api_key,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.center.id', $center->id);

    $this->assertDatabaseHas('users', [
        'phone' => '7778889999',
        'country_code' => '+20',
        'center_id' => $center->id,
    ]);
});

test('verify issues tokens using login action', function (): void {
    /** @var User $user */
    $user = User::factory()->create();
    /** @var MockInterface&LoginAction $action */
    $action = Mockery::mock(LoginAction::class);
    $action->allows()
        ->execute(Mockery::type('array'), null)
        ->andReturn([
            'user' => $user,
            'token' => ['access_token' => 'access', 'refresh_token' => 'refresh'],
        ]);

    $this->app->instance(LoginAction::class, $action);

    $response = $this->postJson('/api/v1/auth/verify', [
        'otp' => '123456',
        'token' => 'token-123',
        'device_uuid' => 'device-1',
    ], [
        'X-Api-Key' => 'system-key',
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['success', 'data', 'token']);
});

test('verify rejects login from another device when active device exists', function (): void {
    /** @var User $user */
    $user = User::factory()->create([
        'phone' => '9990001111',
        'country_code' => '+20',
        'center_id' => null,
    ]);

    UserDevice::factory()->create([
        'user_id' => $user->id,
        'device_id' => 'device-active',
        'status' => UserDevice::STATUS_ACTIVE,
    ]);

    OtpCode::factory()->create([
        'user_id' => $user->id,
        'phone' => '9990001111',
        'country_code' => '+20',
        'otp_code' => '123456',
        'otp_token' => 'token-device-mismatch',
    ]);

    $response = $this->postJson('/api/v1/auth/verify', [
        'otp' => '123456',
        'token' => 'token-device-mismatch',
        'device_uuid' => 'device-new',
    ], [
        'X-Api-Key' => 'system-key',
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'DEVICE_MISMATCH');
});

test('otp cannot be reused after consumption', function (): void {
    /** @var User $user */
    $user = User::factory()->create(['phone' => '5555555555', 'country_code' => '+20', 'center_id' => null]);
    $otp = OtpCode::factory()->create([
        'user_id' => $user->id,
        'phone' => '5555555555',
        'country_code' => '+20',
        'otp_code' => '654321',
        'otp_token' => 'tok-reuse',
    ]);

    $this->postJson('/api/v1/auth/verify', [
        'otp' => '654321',
        'token' => 'tok-reuse',
        'device_uuid' => 'device-reuse',
    ], [
        'X-Api-Key' => 'system-key',
    ])->assertOk();

    $otp->refresh();
    expect($otp->consumed_at)->not->toBeNull();

    $this->postJson('/api/v1/auth/verify', [
        'otp' => '654321',
        'token' => 'tok-reuse',
        'device_uuid' => 'device-reuse',
    ], [
        'X-Api-Key' => 'system-key',
    ])->assertStatus(422);
});

test('refresh returns tokens', function (): void {
    /** @var MockInterface&JwtServiceInterface $jwt */
    $jwt = Mockery::mock(JwtServiceInterface::class);
    /** @var Mockery\ExpectationInterface $expectation */
    $jwt->allows()
        ->refresh('refresh-123')
        ->andReturn(['access_token' => 'access', 'refresh_token' => 'refresh']);

    $this->app->instance(JwtServiceInterface::class, $jwt);

    $response = $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => 'refresh-123',
    ], [
        'X-Api-Key' => 'system-key',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('token.access_token', 'access')
        ->assertJsonPath('token.refresh_token', 'refresh');
});

test('refresh returns empty tokens for invalid refresh token', function (): void {
    $response = $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => 'invalid-refresh',
    ], [
        'X-Api-Key' => 'system-key',
    ]);

    $response->assertOk()
        ->assertJsonPath('token.access_token', '')
        ->assertJsonPath('token.refresh_token', '');
});

test('refresh returns empty tokens when inactive center api key is used', function (): void {
    $center = Center::factory()->create([
        'api_key' => 'inactive-center-key',
        'status' => Center::STATUS_INACTIVE,
    ]);
    $user = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
    ]);
    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
        'status' => UserDevice::STATUS_ACTIVE,
    ]);

    JwtToken::factory()->create([
        'user_id' => $user->id,
        'device_id' => $device->id,
        'refresh_token' => 'inactive-refresh-token',
        'expires_at' => now()->addMinutes(30),
        'refresh_expires_at' => now()->addDay(),
    ]);

    $response = $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => 'inactive-refresh-token',
    ], [
        'X-Api-Key' => $center->api_key,
    ]);

    $response->assertOk()
        ->assertJsonPath('token.access_token', '')
        ->assertJsonPath('token.refresh_token', '');
});
