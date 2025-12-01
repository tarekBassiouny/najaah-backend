<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // --- Core ---
            CenterSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            UserDeviceSeeder::class,

            // --- Course Structure ---
            CategorySeeder::class,
            CourseSeeder::class,
            SectionSeeder::class,
            VideoSeeder::class,
            PdfSeeder::class,

            // --- Learning ---
            EnrollmentSeeder::class,
            PlaybackSessionSeeder::class,

            // --- Logs ---
            AuditLogSeeder::class,

            // --- Settings ---
            SystemSettingSeeder::class,
            CenterSettingSeeder::class,

            // --- Auth / Tokens ---
            JwtTokenSeeder::class,
            OtpCodeSeeder::class,
        ]);
    }
}
