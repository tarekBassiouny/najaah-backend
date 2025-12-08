<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Instructor;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class, Tests\Helpers\AdminTestHelper::class)->group('instructors', 'actions');

test('admin can create instructor', function (): void {
    $admin = $this->asAdmin();
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

    assertDatabaseHas('instructors', [
        'email' => 'jane@example.com',
        'created_by' => $admin->id,
    ]);
});

test('admin can update instructor', function (): void {
    $admin = $this->asAdmin();
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

    assertDatabaseHas('instructors', [
        'id' => $instructor->id,
        'email' => 'new@example.com',
    ]);
});

test('admin can delete instructor', function (): void {
    $admin = $this->asAdmin();
    $instructor = Instructor::factory()->create([
        'created_by' => $admin->id,
    ]);

    $response = $this->deleteJson('/api/v1/instructors/'.$instructor->id);

    $response->assertOk()
        ->assertJsonFragment([
            'success' => true,
            'message' => 'Instructor deleted successfully',
        ]);

    assertSoftDeleted('instructors', ['id' => $instructor->id]);
});

test('validation errors', function (): void {
    $this->asAdmin();

    $response = $this->postJson('/api/v1/instructors', [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422)
        ->assertJsonFragment([
            'success' => false,
        ])
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

test('soft deleted instructors not listed', function (): void {
    $admin = $this->asAdmin();
    Instructor::factory()->create([
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
    expect($response->json('data.0.email'))->toBe('keep@example.com');
});

test('list with pagination', function (): void {
    $admin = $this->asAdmin();
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
    expect(is_countable($data) ? $data : [])->toHaveCount(2);
});

test('show instructor details', function (): void {
    $admin = $this->asAdmin();
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
});
