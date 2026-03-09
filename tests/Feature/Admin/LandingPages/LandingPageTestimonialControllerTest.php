<?php

declare(strict_types=1);

use App\Enums\CenterType;
use App\Models\Center;
use App\Models\CenterLandingPage;
use App\Models\CenterLandingTestimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('landing-pages', 'admin');

it('lists testimonials', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $landingPage = CenterLandingPage::factory()->create(['center_id' => $center->id]);
    CenterLandingTestimonial::factory()->count(3)->create([
        'center_landing_page_id' => $landingPage->id,
    ]);

    $this->asCenterAdmin($center);
    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/landing-page/testimonials", $this->adminHeaders());

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(3, 'data');
});

it('creates a testimonial', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    CenterLandingPage::factory()->create(['center_id' => $center->id]);

    $this->asCenterAdmin($center);
    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/landing-page/testimonials", [
        'author_name' => 'John Doe',
        'author_title' => 'Student',
        'content' => ['en' => 'Great experience!', 'ar' => 'تجربة رائعة!'],
        'rating' => 5,
    ], $this->adminHeaders());

    $response
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.author_name', 'John Doe')
        ->assertJsonPath('data.rating', 5);

    $this->assertDatabaseHas('center_landing_testimonials', [
        'author_name' => 'John Doe',
    ]);
});

it('validates required fields when creating testimonial', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    CenterLandingPage::factory()->create(['center_id' => $center->id]);

    $this->asCenterAdmin($center);
    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/landing-page/testimonials", [
        // Missing required fields
    ], $this->adminHeaders());

    $response
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('updates a testimonial', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $landingPage = CenterLandingPage::factory()->create(['center_id' => $center->id]);
    $testimonial = CenterLandingTestimonial::factory()->create([
        'center_landing_page_id' => $landingPage->id,
        'author_name' => 'Old Name',
    ]);

    $this->asCenterAdmin($center);
    $response = $this->putJson("/api/v1/admin/centers/{$center->id}/landing-page/testimonials/{$testimonial->id}", [
        'author_name' => 'New Name',
        'rating' => 4,
    ], $this->adminHeaders());

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.author_name', 'New Name')
        ->assertJsonPath('data.rating', 4);
});

it('deletes a testimonial', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $landingPage = CenterLandingPage::factory()->create(['center_id' => $center->id]);
    $testimonial = CenterLandingTestimonial::factory()->create([
        'center_landing_page_id' => $landingPage->id,
    ]);

    $this->asCenterAdmin($center);
    $response = $this->deleteJson("/api/v1/admin/centers/{$center->id}/landing-page/testimonials/{$testimonial->id}", [], $this->adminHeaders());

    $response
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertSoftDeleted('center_landing_testimonials', [
        'id' => $testimonial->id,
    ]);
});

it('reorders testimonials', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $landingPage = CenterLandingPage::factory()->create(['center_id' => $center->id]);
    $t1 = CenterLandingTestimonial::factory()->create([
        'center_landing_page_id' => $landingPage->id,
        'sort_order' => 0,
    ]);
    $t2 = CenterLandingTestimonial::factory()->create([
        'center_landing_page_id' => $landingPage->id,
        'sort_order' => 1,
    ]);
    $t3 = CenterLandingTestimonial::factory()->create([
        'center_landing_page_id' => $landingPage->id,
        'sort_order' => 2,
    ]);

    $this->asCenterAdmin($center);
    $response = $this->postJson("/api/v1/admin/centers/{$center->id}/landing-page/testimonials/reorder", [
        'testimonial_ids' => [$t3->id, $t1->id, $t2->id],
    ], $this->adminHeaders());

    $response
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('center_landing_testimonials', ['id' => $t3->id, 'sort_order' => 0]);
    $this->assertDatabaseHas('center_landing_testimonials', ['id' => $t1->id, 'sort_order' => 1]);
    $this->assertDatabaseHas('center_landing_testimonials', ['id' => $t2->id, 'sort_order' => 2]);
});

it('returns 404 for testimonial from different center', function (): void {
    $center1 = Center::factory()->create(['type' => CenterType::Branded]);
    $center2 = Center::factory()->create(['type' => CenterType::Branded]);
    $landingPage = CenterLandingPage::factory()->create(['center_id' => $center2->id]);
    $testimonial = CenterLandingTestimonial::factory()->create([
        'center_landing_page_id' => $landingPage->id,
    ]);

    $this->asCenterAdmin($center1);
    $response = $this->getJson("/api/v1/admin/centers/{$center1->id}/landing-page/testimonials/{$testimonial->id}", $this->adminHeaders());

    $response
        ->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('blocks testimonials endpoints for unbranded centers', function (): void {
    $center = Center::factory()->create([
        'type' => CenterType::Unbranded,
    ]);
    CenterLandingPage::factory()->create(['center_id' => $center->id]);

    $this->asCenterAdmin($center);
    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/landing-page/testimonials", $this->adminHeaders());

    $response
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.message', 'Landing pages are allowed only for branded centers.');
});
