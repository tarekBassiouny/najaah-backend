<?php

declare(strict_types=1);

use App\Enums\CenterType;
use App\Enums\SurveyScopeType;
use App\Models\Center;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class)->group('surveys', 'admin', 'target-students');

it('lists only unbranded and null-center students for system survey targeting', function (): void {
    $this->asAdmin();

    $unbrandedCenter = Center::factory()->create(['type' => CenterType::Unbranded]);
    $brandedCenter = Center::factory()->create(['type' => CenterType::Branded]);

    $unbrandedStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $unbrandedCenter->id,
    ]);
    $nullCenterStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);
    $brandedStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $brandedCenter->id,
    ]);

    $response = $this->getJson(
        '/api/v1/admin/surveys/target-students?scope_type='.SurveyScopeType::System->value.'&per_page=50',
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true);

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($unbrandedStudent->id);
    expect($ids)->toContain($nullCenterStudent->id);
    expect($ids)->not->toContain($brandedStudent->id);
});

it('filters system survey target students by unbranded center id when provided', function (): void {
    $this->asAdmin();

    $centerA = Center::factory()->create(['type' => CenterType::Unbranded]);
    $centerB = Center::factory()->create(['type' => CenterType::Unbranded]);

    $studentA = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerA->id,
    ]);
    $studentB = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerB->id,
    ]);

    $response = $this->getJson(
        '/api/v1/admin/surveys/target-students?scope_type='.SurveyScopeType::System->value.'&center_id='.$centerA->id,
        $this->adminHeaders()
    );

    $response->assertOk()->assertJsonPath('success', true);
    $ids = collect($response->json('data'))->pluck('id')->all();

    expect($ids)->toContain($studentA->id);
    expect($ids)->not->toContain($studentB->id);
});

it('rejects branded center filter for system survey targeting', function (): void {
    $this->asAdmin();

    $brandedCenter = Center::factory()->create(['type' => CenterType::Branded]);

    $response = $this->getJson(
        '/api/v1/admin/surveys/target-students?scope_type='.SurveyScopeType::System->value.'&center_id='.$brandedCenter->id,
        $this->adminHeaders()
    );

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('rejects target students page size above 50', function (): void {
    $this->asAdmin();

    $response = $this->getJson(
        '/api/v1/admin/surveys/target-students?scope_type='.SurveyScopeType::System->value.'&per_page=100',
        $this->adminHeaders()
    );

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('requires center id for center survey targeting', function (): void {
    $this->asAdmin();

    $response = $this->getJson(
        '/api/v1/admin/surveys/target-students?scope_type='.SurveyScopeType::Center->value,
        $this->adminHeaders()
    );

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('lists only selected center students for center survey targeting', function (): void {
    $this->asAdmin();

    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    $studentA = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerA->id,
    ]);
    $studentB = User::factory()->create([
        'is_student' => true,
        'center_id' => $centerB->id,
    ]);

    // Super admin uses center-scoped route for center surveys
    $response = $this->getJson(
        "/api/v1/admin/centers/{$centerA->id}/surveys/target-students",
        $this->adminHeaders()
    );

    $response->assertOk()->assertJsonPath('success', true);
    $ids = collect($response->json('data'))->pluck('id')->all();

    expect($ids)->toContain($studentA->id);
    expect($ids)->not->toContain($studentB->id);
});

it('forbids non-super admin from system survey target-students endpoint', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'survey.manage'], [
        'description' => 'Permission: survey.manage',
    ]);

    $role = Role::factory()->create(['slug' => 'center_admin']);
    $role->permissions()->sync([$permission->id]);

    $center = Center::factory()->create();

    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);
    $admin->roles()->sync([$role->id]);
    $admin->centers()->sync([$center->id => ['type' => 'admin']]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $systemKey = (string) config('services.system_api_key', '');
    if ($systemKey === '') {
        $systemKey = 'system-test-key';
        Config::set('services.system_api_key', $systemKey);
    }

    $response = $this->getJson(
        '/api/v1/admin/surveys/target-students?scope_type='.SurveyScopeType::System->value,
        [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
            'X-Api-Key' => $systemKey,
        ]
    );

    $response->assertForbidden()
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'PERMISSION_DENIED');
});

it('allows center admin to list only own center target students for center scope', function (): void {
    $permission = Permission::firstOrCreate(['name' => 'survey.manage'], [
        'description' => 'Permission: survey.manage',
    ]);

    $role = Role::factory()->create(['slug' => 'center_admin']);
    $role->permissions()->sync([$permission->id]);

    $center = Center::factory()->create();
    $otherCenter = Center::factory()->create();

    $ownStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $center->id,
    ]);
    $otherStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => $otherCenter->id,
    ]);

    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);
    $admin->roles()->sync([$role->id]);
    $admin->centers()->sync([$center->id => ['type' => 'admin']]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $systemKey = (string) config('services.system_api_key', '');
    if ($systemKey === '') {
        $systemKey = 'system-test-key';
        Config::set('services.system_api_key', $systemKey);
    }

    // Center admin uses center-scoped route
    $response = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/surveys/target-students",
        [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
            'X-Api-Key' => $systemKey,
        ]
    );

    $response->assertOk()->assertJsonPath('success', true);
    $ids = collect($response->json('data'))->pluck('id')->all();

    expect($ids)->toContain($ownStudent->id);
    expect($ids)->not->toContain($otherStudent->id);
});
