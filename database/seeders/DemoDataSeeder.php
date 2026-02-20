<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CenterSeeder::class,
            CategorySeeder::class,
            CourseSeeder::class,
            SectionSeeder::class,
            VideoSeeder::class,
            PdfSeeder::class,
            UserSampleSeeder::class,
            InstructorSeeder::class,
            CenterSettingSeeder::class,
            UserDeviceSeeder::class,
            EnrollmentSeeder::class,
            PlaybackSessionSeeder::class,
            AuditLogSeeder::class,
            JwtTokenSeeder::class,
            OtpCodeSeeder::class,
        ]);
    }
}
