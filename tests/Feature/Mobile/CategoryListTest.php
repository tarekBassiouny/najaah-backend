<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Center;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\ApiTestHelper;

uses(RefreshDatabase::class, ApiTestHelper::class)->group('mobile', 'categories');

it('lists categories for branded student center', function (): void {
    $centerA = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $centerB = Center::factory()->create(['type' => 1, 'api_key' => 'center-b-key']);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerA->id,
    ]);
    $student->centers()->syncWithoutDetaching([$centerA->id => ['type' => 'student']]);

    $centerCategory = Category::factory()->create([
        'center_id' => $centerA->id,
        'title_translations' => ['en' => 'Center Category'],
    ]);
    Category::factory()->create([
        'center_id' => $centerB->id,
        'title_translations' => ['en' => 'Other Category'],
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/categories');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $centerCategory->id);
});

it('lists only unbranded categories for system students', function (): void {
    $unbranded = Center::factory()->create(['type' => 0]);
    $branded = Center::factory()->create(['type' => 1]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $systemCategory = Category::factory()->create([
        'center_id' => $unbranded->id,
        'title_translations' => ['en' => 'System Category'],
    ]);
    Category::factory()->create([
        'center_id' => $branded->id,
        'title_translations' => ['en' => 'Branded Category'],
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/categories');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $systemCategory->id);
});

it('searches categories by title', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    $match = Category::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Science Track'],
    ]);
    Category::factory()->create([
        'center_id' => $center->id,
        'title_translations' => ['en' => 'Arts Track'],
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/categories?search=Science');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);
});

it('paginates categories list', function (): void {
    $center = Center::factory()->create(['type' => 1, 'api_key' => 'center-a-key']);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $student->centers()->syncWithoutDetaching([$center->id => ['type' => 'student']]);

    Category::factory()->count(2)->create([
        'center_id' => $center->id,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/categories?per_page=1&page=2');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.page', 2)
        ->assertJsonPath('meta.per_page', 1);
});

it('lists categories for a specific center endpoint', function (): void {
    $centerA = Center::factory()->create(['type' => 0]);
    $centerB = Center::factory()->create(['type' => 0]);

    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $categoryA = Category::factory()->create([
        'center_id' => $centerA->id,
        'title_translations' => ['en' => 'Center A Category'],
    ]);
    Category::factory()->create([
        'center_id' => $centerB->id,
        'title_translations' => ['en' => 'Center B Category'],
    ]);
    Category::factory()->create([
        'center_id' => null,
        'title_translations' => ['en' => 'Global Category'],
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/centers/'.$centerA->id.'/categories');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $categoryA->id);
});

it('returns not found for branded center categories endpoint', function (): void {
    $center = Center::factory()->create(['type' => 1]);
    $student = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);

    $this->asApiUser($student);

    $response = $this->apiGet('/api/v1/centers/'.$center->id.'/categories');
    $response->assertStatus(404);
});
