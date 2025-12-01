<?php

namespace Database\Seeders;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Database\Seeder;

class OtpCodeSeeder extends Seeder
{
    public function run(): void
    {
        // Create 10 pending OTPs
        OtpCode::factory()->count(5)->create();

        // Create OTPs associated with users
        User::limit(5)->get()->each(function ($user) {
            OtpCode::factory()->create([
                'user_id' => $user->id,
                'phone' => $user->phone,
            ]);
        });
    }
}
