<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\UserDevice;
use App\Services\DeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceServiceTest extends TestCase
{
    use RefreshDatabase;

    private ?DeviceService $service = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DeviceService;
    }

    public function test_register_creates_new_device(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->assertNotNull($this->service);
        $device = $this->service->register($user, 'device-123', [
            'device_name' => 'iPhone',
            'device_os' => 'iOS',
        ]);

        $this->assertInstanceOf(UserDevice::class, $device);
        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'device_id' => 'device-123',
            'model' => 'iPhone',
            'os_version' => 'iOS',
            'status' => 0,
        ]);
    }

    public function test_register_updates_existing_device(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var UserDevice $existing */
        $existing = UserDevice::factory()->create([
            'user_id' => $user->id,
            'device_id' => 'device-123',
            'model' => 'Old',
            'os_version' => 'old-os',
            'status' => 1,
        ]);

        $this->assertNotNull($this->service);
        $device = $this->service->register($user, 'device-123', [
            'device_name' => 'New Name',
            'device_os' => 'NewOS',
        ]);

        $this->assertTrue($device->is($existing));
        $this->assertDatabaseHas('user_devices', [
            'id' => $existing->id,
            'model' => 'New Name',
            'os_version' => 'NewOS',
            'status' => 0,
        ]);
    }
}
