<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\College;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('education', 'admin');

it('manages center colleges and lookup', function (): void {
    $center = Center::factory()->create();
    $this->asCenterAdmin($center);

    $create = $this->postJson("/api/v1/admin/centers/{$center->id}/colleges", [
        'name_translations' => ['en' => 'Cairo University', 'ar' => 'جامعة القاهرة'],
        'type' => 1,
        'is_active' => true,
    ], $this->adminHeaders());

    $create->assertCreated()->assertJsonPath('data.name', 'Cairo University');
    $collegeId = (int) $create->json('data.id');
    $inactiveCollege = College::factory()->create([
        'center_id' => $center->id,
        'is_active' => false,
    ]);

    $list = $this->getJson("/api/v1/admin/centers/{$center->id}/colleges", $this->adminHeaders());
    $list->assertOk()->assertJsonCount(2, 'data');

    $lookup = $this->getJson("/api/v1/admin/centers/{$center->id}/colleges/lookup", $this->adminHeaders());
    $lookup->assertOk()->assertJsonCount(2, 'data');

    $lookupActiveOnly = $this->getJson("/api/v1/admin/centers/{$center->id}/colleges/lookup?is_active=true", $this->adminHeaders());
    $lookupActiveOnly->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $collegeId);

    $lookupInactiveOnly = $this->getJson("/api/v1/admin/centers/{$center->id}/colleges/lookup?is_active=false", $this->adminHeaders());
    $lookupInactiveOnly->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $inactiveCollege->id);
});
