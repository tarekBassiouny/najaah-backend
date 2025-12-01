<?php

namespace Database\Seeders;

use App\Models\JwtToken;
use App\Models\UserDevice;
use Illuminate\Database\Seeder;

class JwtTokenSeeder extends Seeder
{
    public function run(): void
    {
        UserDevice::all()->each(function (UserDevice $device): void {
            JwtToken::factory()->create([
                'user_id' => $device->user_id,
                'device_id' => $device->id,
            ]);
        });
    }
}
