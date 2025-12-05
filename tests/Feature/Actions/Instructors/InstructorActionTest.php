<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Instructors;

use App\Models\Center;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InstructorActionTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'is_student' => false,
            'center_id' => null,
        ]);

        Sanctum::actingAs($admin, ['*']);

        return $admin;
    }

    public function test_admin_can_create_instructor(): void
    {
        $admin = $this->actingAsAdmin();
        $center = Center::factory()->create();

        $payload = [
            'center_id' => $center->id,
            'name_translations' => ['en' => 'Jane Doe', 'ar' => 'جين'],
            'bio_translations' => ['en' => 'Bio'],
            'title_translations' => ['en' => 'Professor'],
            'avatar_url' => 'https://example.com/avatar.jpg',
            'email' => 'jane@example.com',
            'phone' => '123456789',
            'social_links' => ['facebook' => 'https://fb.com/jane'],
        ];

        $response = $this->postJson('/api/v1/instructors', $payload);

        $response->assertCreated()
            ->assertJsonFragment([
                'success' => true,
                'message' => 'Instructor created successfully',
            ])
            ->assertJsonPath('data.email', 'jane@example.com');

        $this->assertDatabaseHas('instructors', [
            'email' => 'jane@example.com',
            'created_by' => $admin->id,
        ]);
    }

    public function test_admin_can_update_instructor(): void
    {
        $admin = $this->actingAsAdmin();
        $instructor = Instructor::factory()->create([
            'created_by' => $admin->id,
            'email' => 'old@example.com',
        ]);

        $response = $this->putJson('/api/v1/instructors/'.$instructor->id, [
            'name_translations' => ['en' => 'Updated Name'],
            'email' => 'new@example.com',
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'success' => true,
                'message' => 'Instructor updated successfully',
            ])
            ->assertJsonPath('data.email', 'new@example.com');

        $this->assertDatabaseHas('instructors', [
            'id' => $instructor->id,
            'email' => 'new@example.com',
        ]);
    }

    public function test_admin_can_delete_instructor(): void
    {
        $admin = $this->actingAsAdmin();
        $instructor = Instructor::factory()->create([
            'created_by' => $admin->id,
        ]);

        $response = $this->deleteJson('/api/v1/instructors/'.$instructor->id);

        $response->assertOk()
            ->assertJsonFragment([
                'success' => true,
                'message' => 'Instructor deleted successfully',
            ]);

        $this->assertSoftDeleted('instructors', ['id' => $instructor->id]);
    }

    public function test_validation_errors(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/instructors', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'success' => false,
            ])
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_soft_deleted_instructors_not_listed(): void
    {
        $admin = $this->actingAsAdmin();
        $instructor = Instructor::factory()->create([
            'created_by' => $admin->id,
            'email' => 'keep@example.com',
        ]);
        $deleted = Instructor::factory()->create([
            'created_by' => $admin->id,
            'email' => 'remove@example.com',
        ]);
        $this->deleteJson('/api/v1/instructors/'.$deleted->id)->assertOk();

        $response = $this->getJson('/api/v1/instructors');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1);
        $response->assertJsonMissingPath('data.1.email');
        $this->assertEquals('keep@example.com', $response->json('data.0.email'));
    }

    public function test_list_with_pagination(): void
    {
        $admin = $this->actingAsAdmin();
        Instructor::factory()->count(3)->create([
            'created_by' => $admin->id,
        ]);

        $response = $this->getJson('/api/v1/instructors?per_page=2');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.total', 3);

        /** @var array<mixed> $data */
        $data = $response->json('data');
        $this->assertCount(2, is_countable($data) ? $data : []);
    }

    public function test_show_instructor_details(): void
    {
        $admin = $this->actingAsAdmin();
        $instructor = Instructor::factory()->create([
            'created_by' => $admin->id,
            'email' => 'show@example.com',
        ]);

        $response = $this->getJson('/api/v1/instructors/'.$instructor->id);

        $response->assertOk()
            ->assertJsonFragment([
                'success' => true,
            ])
            ->assertJsonPath('data.email', 'show@example.com')
            ->assertJsonPath('data.id', $instructor->id);
    }
}
