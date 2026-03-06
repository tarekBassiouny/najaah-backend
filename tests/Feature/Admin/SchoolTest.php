<?php

declare(strict_types=1);

use App\Enums\SchoolType;
use App\Models\Center;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('education', 'admin');

it('manages center schools and lookup', function (): void {
    $center = Center::factory()->create();
    $this->asCenterAdmin($center);

    $create = $this->postJson("/api/v1/admin/centers/{$center->id}/schools", [
        'name_translations' => ['en' => 'Alpha School', 'ar' => 'مدرسة ألفا'],
        'type' => SchoolType::International->value,
        'is_active' => true,
    ], $this->adminHeaders());

    $create->assertCreated()->assertJsonPath('data.name', 'Alpha School');
    $schoolId = (int) $create->json('data.id');

    $list = $this->getJson("/api/v1/admin/centers/{$center->id}/schools?type=2", $this->adminHeaders());
    $list->assertOk()->assertJsonCount(1, 'data');

    $lookup = $this->getJson("/api/v1/admin/centers/{$center->id}/schools/lookup", $this->adminHeaders());
    $lookup->assertOk()->assertJsonPath('data.0.id', $schoolId);
});
