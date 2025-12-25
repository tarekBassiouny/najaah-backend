<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\CenterSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('public-centers');

it('returns public center discovery data', function (): void {
    $center = Center::factory()->create([
        'slug' => 'alpha-center',
        'type' => 1,
        'logo_url' => 'https://example.com/logo.png',
        'primary_color' => '#112233',
    ]);

    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'branding' => [
                'primary_color' => '#445566',
            ],
        ],
    ]);

    $response = $this->getJson('/api/v1/admin/centers/alpha-center');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.slug', 'alpha-center')
        ->assertJsonPath('data.type', 'branded')
        ->assertJsonPath('data.logo', 'https://example.com/logo.png')
        ->assertJsonPath('data.theme.primary_color', '#445566');
});

it('returns not found for unknown center slug', function (): void {
    $response = $this->getJson('/api/v1/admin/centers/missing-center');

    $response->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'NOT_FOUND');
});
