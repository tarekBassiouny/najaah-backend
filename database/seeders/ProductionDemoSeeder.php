<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\VideoUploadStatus;
use App\Models\Center;
use App\Models\Course;
use App\Models\Pivots\CourseVideo;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use App\Models\Video;
use App\Services\Centers\CenterOnboardingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ProductionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->guardEnvironment();

        $creator = $this->resolveSuperAdmin();
        $onboardingService = app(CenterOnboardingService::class);
        $counts = [
            'super_admin_created' => 0,
            'super_admin_reused' => 0,
            'centers_created' => 0,
            'centers_reused' => 0,
            'courses_created' => 0,
            'courses_reused' => 0,
            'sections_created' => 0,
            'sections_reused' => 0,
            'videos_created' => 0,
            'videos_reused' => 0,
            'attachments_created' => 0,
            'attachments_reused' => 0,
        ];

        Log::info('Production demo seeding started.');

        if ($creator->wasRecentlyCreated) {
            $counts['super_admin_created']++;
        } else {
            $counts['super_admin_reused']++;
        }

        foreach ($this->demoCenters() as $centerPayload) {
            $center = $this->upsertCenter($centerPayload, $counts);
            $onboardingService->ensureSettingsAndStorage($center);

            foreach ($centerPayload['courses'] as $coursePayload) {
                $course = $this->upsertCourse($center, $creator, $coursePayload, $counts);

                foreach ($coursePayload['sections'] as $sectionIndex => $sectionPayload) {
                    $section = $this->upsertSection($course, $sectionIndex + 1, $sectionPayload, $counts);

                    foreach ($sectionPayload['videos'] as $videoIndex => $videoPayload) {
                        $video = $this->upsertVideo(
                            $center,
                            $course,
                            $section,
                            $creator,
                            $videoIndex + 1,
                            $videoPayload,
                            $counts
                        );

                        $this->upsertAttachment($course, $section, $video, $videoIndex + 1, $counts);
                    }
                }
            }
        }

        Log::info('Production demo seeding completed.', $counts);
    }

    private function guardEnvironment(): void
    {
        if (! app()->environment('production')) {
            throw new RuntimeException('ProductionDemoSeeder can only run in production.');
        }

        if (! config('demo.enabled')) {
            throw new RuntimeException('Demo seeding is disabled.');
        }
    }

    private function resolveSuperAdmin(): User
    {
        $role = Role::where('slug', 'super_admin')->first();
        if ($role instanceof Role) {
            $existingAdmins = $role->users()
                ->where('is_student', false)
                ->whereNull('center_id')
                ->get();

            if ($existingAdmins->count() > 1) {
                throw new RuntimeException('Multiple super admin users detected. Expected exactly one.');
            }

            if ($existingAdmins->count() === 1) {
                return $existingAdmins->first();
            }
        }

        $email = 'admin@mail.com';
        $phone = '01234567890';

        $existing = User::query()->where('email', $email)->first();
        if ($existing instanceof User) {
            if ($existing->is_student || $existing->center_id !== null) {
                throw new RuntimeException('Demo super admin email is already in use by a non-admin user.');
            }

            if ($existing->phone !== $phone) {
                throw new RuntimeException('Demo super admin phone does not match existing user.');
            }

            $this->attachSuperAdminRole($existing);

            return $existing;
        }

        $superAdmin = User::create([
            'name' => 'System Admin',
            'phone' => '01234567890',
            'country_code' => '002',
            'email' => 'admin@mail.com',
            'password' => Hash::make('password'),
            'center_id' => null,
            'is_student' => false,
            'status' => 1,
        ]);

        $this->attachSuperAdminRole($superAdmin);

        return $superAdmin;
    }

    private function attachSuperAdminRole(User $user): void
    {
        $role = Role::updateOrCreate(
            ['slug' => 'super_admin'],
            [
                'name' => 'super admin',
                'name_translations' => [
                    'en' => 'super admin',
                    'ar' => 'دور super_admin',
                ],
                'description_translations' => [
                    'en' => 'Full system administrator',
                    'ar' => 'وصف super_admin',
                ],
            ]
        );

        $user->roles()->syncWithoutDetaching([$role->id]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function demoCenters(): array
    {
        return [
            [
                'slug' => 'demo-center-1',
                'name' => 'Demo Center branded 1',
                'description' => 'Demo catalog for product walkthroughs.',
                'api_key' => '123456789',
                'courses' => [
                    [
                        'code' => 'demo-course-1',
                        'title' => 'Demo Course 1',
                        'description' => 'Explore the core features in a guided flow.',
                        'publish_at' => Carbon::create(2024, 1, 10, 0, 0, 0, 'UTC'),
                        'sections' => [
                            [
                                'title' => 'Getting Started',
                                'description' => 'Key concepts and navigation basics.',
                                'videos' => [
                                    ['title' => 'Welcome to the Platform', 'duration' => 320],
                                    ['title' => 'Finding Your Way Around', 'duration' => 540],
                                ],
                            ],
                            [
                                'title' => 'Core Lessons',
                                'description' => 'Practice with real workflows.',
                                'videos' => [
                                    ['title' => 'Structuring Your Learning', 'duration' => 780],
                                    ['title' => 'Tracking Progress', 'duration' => 610],
                                ],
                            ],
                        ],
                    ],
                    [
                        'code' => 'demo-course-2',
                        'title' => 'Demo Course 2',
                        'description' => 'Highlights for course creation and publishing.',
                        'publish_at' => Carbon::create(2024, 2, 5, 0, 0, 0, 'UTC'),
                        'sections' => [
                            [
                                'title' => 'Planning Content',
                                'description' => 'Outline your course structure.',
                                'videos' => [
                                    ['title' => 'Setting Learning Goals', 'duration' => 420],
                                    ['title' => 'Organizing Modules', 'duration' => 640],
                                ],
                            ],
                            [
                                'title' => 'Publishing',
                                'description' => 'Finalize and publish courses.',
                                'videos' => [
                                    ['title' => 'Preview and QA', 'duration' => 510],
                                    ['title' => 'Launching to Students', 'duration' => 470],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'demo-center-2',
                'name' => 'Demo Center unbranded 2',
                'description' => 'Demo content for enterprise workflows.',
                'type' => 0,
                'courses' => [
                    [
                        'code' => 'demo-course-3',
                        'title' => 'Demo Course 3',
                        'description' => 'Scale operations with analytics and reporting.',
                        'publish_at' => Carbon::create(2024, 3, 12, 0, 0, 0, 'UTC'),
                        'sections' => [
                            [
                                'title' => 'Insights Overview',
                                'description' => 'Dashboards and key metrics.',
                                'videos' => [
                                    ['title' => 'Reading Engagement Data', 'duration' => 560],
                                    ['title' => 'Exporting Reports', 'duration' => 450],
                                ],
                            ],
                            [
                                'title' => 'Actionable Steps',
                                'description' => 'Turn data into improvements.',
                                'videos' => [
                                    ['title' => 'Identifying Drop-offs', 'duration' => 630],
                                    ['title' => 'Iterating on Content', 'duration' => 520],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsertCenter(array $payload, array &$counts): Center
    {
        $existing = Center::where('slug', $payload['slug'])->first();
        if ($existing instanceof Center && ! $existing->is_demo) {
            throw new RuntimeException('Center slug '.$payload['slug'].' is already used by non-demo data.');
        }

        $center = Center::updateOrCreate(
            ['slug' => $payload['slug']],
            [
                'type' => 1,
                'tier' => Center::TIER_PREMIUM,
                'is_featured' => true,
                'is_demo' => true,
                'onboarding_status' => Center::ONBOARDING_ACTIVE,
                'name_translations' => ['en' => $payload['name']],
                'description_translations' => ['en' => $payload['description']],
                'branding_metadata' => [
                    'demo' => true,
                    'source' => 'ProductionDemoSeeder',
                ],
            ]
        );

        if ($center->wasRecentlyCreated) {
            $counts['centers_created']++;
        } else {
            $counts['centers_reused']++;
        }

        return $center;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsertCourse(Center $center, User $creator, array $payload, array &$counts): Course
    {
        $existing = Course::query()
            ->where('center_id', $center->id)
            ->where('course_code', $payload['code'])
            ->first();

        if ($existing instanceof Course && ! $existing->is_demo) {
            throw new RuntimeException('Course code '.$payload['code'].' is already used by non-demo data.');
        }

        $durationMinutes = $this->estimateDurationMinutes($payload['sections']);

        $course = Course::updateOrCreate(
            [
                'center_id' => $center->id,
                'course_code' => $payload['code'],
            ],
            [
                'title_translations' => ['en' => $payload['title']],
                'description_translations' => ['en' => $payload['description']],
                'difficulty_level' => 3,
                'language' => 'en',
                'status' => 3,
                'is_published' => true,
                'duration_minutes' => $durationMinutes,
                'is_featured' => true,
                'is_demo' => true,
                'created_by' => $creator->id,
                'publish_at' => $payload['publish_at'],
            ]
        );

        if ($course->wasRecentlyCreated) {
            $counts['courses_created']++;
        } else {
            $counts['courses_reused']++;
        }

        return $course;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsertSection(Course $course, int $orderIndex, array $payload, array &$counts): Section
    {
        $existing = Section::query()
            ->where('course_id', $course->id)
            ->where('order_index', $orderIndex)
            ->first();

        if ($existing instanceof Section && ! $existing->is_demo) {
            throw new RuntimeException('Section order '.$orderIndex.' is already used by non-demo data.');
        }

        $section = Section::updateOrCreate(
            [
                'course_id' => $course->id,
                'order_index' => $orderIndex,
            ],
            [
                'title_translations' => ['en' => $payload['title']],
                'description_translations' => ['en' => $payload['description']],
                'visible' => true,
                'is_demo' => true,
            ]
        );

        if ($section->wasRecentlyCreated) {
            $counts['sections_created']++;
        } else {
            $counts['sections_reused']++;
        }

        return $section;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsertVideo(
        Center $center,
        Course $course,
        Section $section,
        User $creator,
        int $videoIndex,
        array $payload,
        array &$counts
    ): Video {
        $sourceUrl = $this->demoSourceUrl($center->slug, (string) $course->course_code, $section->order_index, $videoIndex);
        $existing = Video::query()->where('source_url', $sourceUrl)->first();

        if ($existing instanceof Video && ! $existing->is_demo) {
            throw new RuntimeException('Video source URL '.$sourceUrl.' is already used by non-demo data.');
        }

        $video = Video::updateOrCreate(
            ['source_url' => $sourceUrl],
            [
                'title_translations' => ['en' => $payload['title']],
                'description_translations' => ['en' => 'Demo video content.'],
                'source_type' => 1,
                'source_provider' => 'bunny',
                'source_id' => null,
                'duration_seconds' => $payload['duration'],
                'lifecycle_status' => 2,
                'encoding_status' => VideoUploadStatus::Ready,
                'tags' => [
                    'demo' => true,
                    'source' => 'ProductionDemoSeeder',
                ],
                'created_by' => $creator->id,
                'is_demo' => true,
            ]
        );

        if ($video->wasRecentlyCreated) {
            $counts['videos_created']++;
        } else {
            $counts['videos_reused']++;
        }

        return $video;
    }

    private function upsertAttachment(Course $course, Section $section, Video $video, int $orderIndex, array &$counts): void
    {
        $attachment = CourseVideo::updateOrCreate(
            [
                'course_id' => $course->id,
                'video_id' => $video->id,
                'section_id' => $section->id,
            ],
            [
                'order_index' => $orderIndex,
                'visible' => true,
            ]
        );

        if ($attachment->wasRecentlyCreated) {
            $counts['attachments_created']++;
        } else {
            $counts['attachments_reused']++;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     */
    private function estimateDurationMinutes(array $sections): int
    {
        $seconds = 0;

        foreach ($sections as $section) {
            foreach ($section['videos'] as $video) {
                $seconds += (int) $video['duration'];
            }
        }

        return (int) max(1, (int) ceil($seconds / 60));
    }

    private function demoSourceUrl(string $centerSlug, string $courseCode, int $sectionIndex, int $videoIndex): string
    {
        return sprintf('demo://%s/%s/section-%d/video-%d', $centerSlug, $courseCode, $sectionIndex, $videoIndex);
    }
}
