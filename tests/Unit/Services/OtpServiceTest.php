<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\OtpCode;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private ?OtpService $service = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OtpService;
    }

    public function test_send_creates_otp_record_and_returns_token(): void
    {
        $this->assertNotNull($this->service);
        $result = $this->service->send('+201234567890', '+20');

        $this->assertArrayHasKey('token', $result);
        $this->assertIsString($result['token']);

        $this->assertDatabaseHas('otp_codes', [
            'phone' => '+201234567890',
            'otp_token' => $result['token'],
        ]);
    }

    public function test_verify_returns_otp_code_when_valid(): void
    {
        /** @var OtpCode $code */
        $code = OtpCode::factory()->create([
            'otp_code' => '123456',
            'otp_token' => 'token-123',
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->assertNotNull($this->service);
        $result = $this->service->verify('123456', 'token-123');

        $this->assertNotNull($result);
        $this->assertTrue($result->is($code));
    }

    public function test_verify_returns_null_when_expired(): void
    {
        OtpCode::factory()->create([
            'otp_code' => '123456',
            'otp_token' => 'token-123',
            'expires_at' => now()->subMinute(),
        ]);

        $this->assertNotNull($this->service);
        $result = $this->service->verify('123456', 'token-123');

        $this->assertNull($result);
    }
}
