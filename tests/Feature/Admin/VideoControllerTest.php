<?php

declare(strict_types=1);

use App\Models\Center;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('videos', 'admin');

it('rejects array title payload when creating video', function (): void {
    $center = Center::factory()->create();
    $admin = $this->asAdmin();
    $admin->update(['center_id' => $center->id]);

    $response = $this->actingAs($admin, 'admin')->postJson(
        "/api/v1/admin/centers/{$center->id}/videos",
        [
            'title' => ['en' => 'Bad'],
            'description' => 'Sample description',
        ]
    );

    $response->assertStatus(422);
});
