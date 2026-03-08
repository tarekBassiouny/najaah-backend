<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\CenterLandingPage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('landing-pages', 'admin');

it('updates meta section', function (): void {
    $center = Center::factory()->create();
    CenterLandingPage::factory()->create(['center_id' => $center->id]);

    $this->asCenterAdmin($center);
    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/landing-page/sections/meta", [
        'meta_title' => 'New Title',
        'meta_description' => 'New Description',
        'meta_keywords' => 'education, learning',
    ], $this->adminHeaders());

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.meta.title', 'New Title')
        ->assertJsonPath('data.meta.description', 'New Description');
});

it('updates hero section with translations', function (): void {
    $center = Center::factory()->create();
    CenterLandingPage::factory()->create(['center_id' => $center->id]);

    $this->asCenterAdmin($center);
    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/landing-page/sections/hero", [
        'hero_title' => ['en' => 'Welcome', 'ar' => 'مرحبا'],
        'hero_subtitle' => ['en' => 'Learn with us', 'ar' => 'تعلم معنا'],
        'hero_cta_text' => 'Get Started',
        'hero_cta_url' => '/register',
    ], $this->adminHeaders());

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.hero.title_translations.en', 'Welcome')
        ->assertJsonPath('data.hero.title_translations.ar', 'مرحبا');
});

it('updates about section', function (): void {
    $center = Center::factory()->create();
    CenterLandingPage::factory()->create(['center_id' => $center->id]);

    $this->asCenterAdmin($center);
    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/landing-page/sections/about", [
        'about_title' => ['en' => 'About Us', 'ar' => 'معلومات عنا'],
        'about_content' => ['en' => 'We are a leading center...', 'ar' => 'نحن مركز رائد...'],
    ], $this->adminHeaders());

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.about.title_translations.en', 'About Us');
});

it('updates contact section', function (): void {
    $center = Center::factory()->create();
    CenterLandingPage::factory()->create(['center_id' => $center->id]);

    $this->asCenterAdmin($center);
    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/landing-page/sections/contact", [
        'contact_email' => 'contact@example.com',
        'contact_phone' => '+20 123 456 7890',
        'contact_address' => '123 Education Street',
    ], $this->adminHeaders());

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.contact.email', 'contact@example.com')
        ->assertJsonPath('data.contact.phone', '+20 123 456 7890');
});

it('updates social section', function (): void {
    $center = Center::factory()->create();
    CenterLandingPage::factory()->create(['center_id' => $center->id]);

    $this->asCenterAdmin($center);
    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/landing-page/sections/social", [
        'social_facebook' => 'https://facebook.com/mycenter',
        'social_instagram' => 'https://instagram.com/mycenter',
    ], $this->adminHeaders());

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.social.facebook', 'https://facebook.com/mycenter');
});

it('updates styling section', function (): void {
    $center = Center::factory()->create();
    CenterLandingPage::factory()->create(['center_id' => $center->id]);

    $this->asCenterAdmin($center);
    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/landing-page/sections/styling", [
        'primary_color' => '#3B82F6',
        'secondary_color' => '#1E40AF',
        'font_family' => 'Inter',
    ], $this->adminHeaders());

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.styling.primary_color', '#3B82F6');
});

it('validates hex color format', function (): void {
    $center = Center::factory()->create();
    CenterLandingPage::factory()->create(['center_id' => $center->id]);

    $this->asCenterAdmin($center);
    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/landing-page/sections/styling", [
        'primary_color' => 'not-a-color',
    ], $this->adminHeaders());

    $response
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('updates visibility section', function (): void {
    $center = Center::factory()->create();
    CenterLandingPage::factory()->create(['center_id' => $center->id]);

    $this->asCenterAdmin($center);
    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/landing-page/sections/visibility", [
        'show_hero' => true,
        'show_about' => false,
        'show_courses' => true,
        'show_testimonials' => false,
        'show_contact' => true,
    ], $this->adminHeaders());

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.visibility.show_hero', true)
        ->assertJsonPath('data.visibility.show_about', false);
});
