<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Pdf;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('videos', 'pdfs', 'admin');

it('returns not found for video center mismatch', function (): void {
    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();
    $admin = $this->asAdmin();
    $admin->update(['center_id' => $centerA->id]);

    $video = Video::factory()->create([
        'center_id' => $centerB->id,
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin, 'admin')->getJson(
        "/api/v1/admin/centers/{$centerA->id}/videos/{$video->id}",
        $this->adminHeaders()
    );

    $response->assertNotFound()->assertJsonPath('error.code', 'NOT_FOUND');
});

it('returns not found for pdf center mismatch', function (): void {
    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();
    $admin = $this->asAdmin();
    $admin->update(['center_id' => $centerA->id]);

    $pdf = Pdf::factory()->create([
        'center_id' => $centerB->id,
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin, 'admin')->getJson(
        "/api/v1/admin/centers/{$centerA->id}/pdfs/{$pdf->id}",
        $this->adminHeaders()
    );

    $response->assertNotFound()->assertJsonPath('error.code', 'NOT_FOUND');
});
