<?php

declare(strict_types=1);

namespace Tests\Feature\Mobile;

use App\Enums\CenterType;
use App\Enums\CourseStatus;
use App\Models\Center;
use App\Models\Course;
use App\Models\Instructor;
use Database\Factories\CategoryFactory;
use Database\Factories\CenterFactory;
use Database\Factories\CourseFactory;
use Database\Factories\InstructorFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestBrowsingFeatureTest extends TestCase
{
    use RefreshDatabase;

    private Center $centerWithGuestBrowsing;

    private Center $centerWithoutGuestBrowsing;

    private Course $publishedCourse;

    protected function setUp(): void
    {
        parent::setUp();

        // Create center that allows guest browsing
        $this->centerWithGuestBrowsing = CenterFactory::new()->create([
            'type' => CenterType::Unbranded->value,
            'status' => Center::STATUS_ACTIVE->value,
            'allow_guest_browsing' => true,
        ]);

        // Create center that does NOT allow guest browsing
        $this->centerWithoutGuestBrowsing = CenterFactory::new()->create([
            'type' => CenterType::Unbranded->value,
            'status' => Center::STATUS_ACTIVE->value,
            'allow_guest_browsing' => false,
        ]);

        // Create published course for center with guest browsing
        $this->publishedCourse = CourseFactory::new()->create([
            'center_id' => $this->centerWithGuestBrowsing->id,
            'status' => CourseStatus::Published,
            'is_published' => true,
            'show_for_all_students' => true,
        ]);
    }

    public function test_guest_can_explore_courses_from_centers_with_guest_browsing_enabled(): void
    {
        $response = $this->getJson('/api/v1/courses/explore');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Should return courses from centers with guest browsing enabled
        $courseIds = collect($response->json('data'))->pluck('id');
        $this->assertTrue($courseIds->contains($this->publishedCourse->id));
    }

    public function test_guest_cannot_see_courses_from_centers_without_guest_browsing(): void
    {
        // Create course in center without guest browsing
        $hiddenCourse = CourseFactory::new()->create([
            'center_id' => $this->centerWithoutGuestBrowsing->id,
            'status' => CourseStatus::Published,
            'is_published' => true,
            'show_for_all_students' => true,
        ]);

        $response = $this->getJson('/api/v1/courses/explore');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Should NOT return courses from centers without guest browsing
        $courseIds = collect($response->json('data'))->pluck('id');
        $this->assertFalse($courseIds->contains($hiddenCourse->id));
    }

    public function test_guest_can_search_courses_from_centers_with_guest_browsing(): void
    {
        $response = $this->getJson('/api/v1/search');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_guest_can_list_centers_with_guest_browsing_enabled(): void
    {
        $response = $this->getJson('/api/v1/centers');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Should return centers with guest browsing enabled
        $centerIds = collect($response->json('data'))->pluck('id');
        $this->assertTrue($centerIds->contains($this->centerWithGuestBrowsing->id));
        $this->assertFalse($centerIds->contains($this->centerWithoutGuestBrowsing->id));
    }

    public function test_guest_can_view_center_details_when_guest_browsing_enabled(): void
    {
        $response = $this->getJson("/api/v1/centers/{$this->centerWithGuestBrowsing->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_guest_cannot_view_center_details_when_guest_browsing_disabled(): void
    {
        $response = $this->getJson("/api/v1/centers/{$this->centerWithoutGuestBrowsing->id}");

        // Guest browsing middleware returns 403 Forbidden when guest browsing is disabled
        $response->assertStatus(403)
            ->assertJsonPath('error.code', 'GUEST_BROWSING_NOT_ALLOWED');
    }

    public function test_guest_cannot_see_center_or_courses_when_guest_browsing_feature_flag_is_disabled(): void
    {
        $this->centerWithGuestBrowsing->setting()->create([
            'settings' => [
                'features' => [
                    'guest_browsing' => false,
                ],
            ],
        ]);

        $centerList = $this->getJson('/api/v1/centers');

        $centerList->assertStatus(200)
            ->assertJsonPath('success', true);

        $centerIds = collect($centerList->json('data'))->pluck('id');
        $this->assertFalse($centerIds->contains($this->centerWithGuestBrowsing->id));

        $courses = $this->getJson('/api/v1/courses/explore');

        $courses->assertStatus(200)
            ->assertJsonPath('success', true);

        $courseIds = collect($courses->json('data'))->pluck('id');
        $this->assertFalse($courseIds->contains($this->publishedCourse->id));
    }

    public function test_guest_can_list_categories(): void
    {
        CategoryFactory::new()->create([
            'center_id' => $this->centerWithGuestBrowsing->id,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_guest_can_list_instructors_from_centers_with_guest_browsing(): void
    {
        InstructorFactory::new()->create([
            'center_id' => $this->centerWithGuestBrowsing->id,
        ]);

        $response = $this->getJson('/api/v1/instructors');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_guest_cannot_list_instructors_from_centers_without_guest_browsing(): void
    {
        // Create instructor only in center without guest browsing
        $instructor = InstructorFactory::new()->create([
            'center_id' => $this->centerWithoutGuestBrowsing->id,
        ]);

        $response = $this->getJson('/api/v1/instructors');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Instructor should not be in the response
        $instructorIds = collect($response->json('data'))->pluck('id');
        $this->assertFalse($instructorIds->contains($instructor->id));
    }

    public function test_allow_guest_browsing_field_is_returned_in_admin_center_resource(): void
    {
        $this->assertTrue($this->centerWithGuestBrowsing->allow_guest_browsing);
        $this->assertFalse($this->centerWithoutGuestBrowsing->allow_guest_browsing);
    }

    public function test_guest_can_see_courses_regardless_of_show_for_all_students_flag(): void
    {
        // Create course with show_for_all_students = false
        $targetedCourse = CourseFactory::new()->create([
            'center_id' => $this->centerWithGuestBrowsing->id,
            'status' => CourseStatus::Published,
            'is_published' => true,
            'show_for_all_students' => false,
        ]);

        $response = $this->getJson('/api/v1/courses/explore');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Guests should see all published courses from guest-browsing-enabled centers
        $courseIds = collect($response->json('data'))->pluck('id');
        $this->assertTrue($courseIds->contains($targetedCourse->id));
    }
}
