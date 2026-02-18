<?php

declare(strict_types=1);

use App\Enums\SurveyScopeType;
use App\Models\Center;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class)->group('surveys', 'admin', 'target-students');

it('lists only students without center for system survey targeting', function (): void {
    $this->asAdmin();

    $nullCenterStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => null,
    ]);
    $centerStudent = User::factory()->create([
        'is_student' => true,
        'center_id' => Center::factory()->create()->id,
    ]);

    $response = $this->getJson(
        '/api/v1/admin/surveys/target-students?scope_type='.SurveyScopeType::System->value.'&per_page=50',
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('success', true);

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($nullCenterStudent->id);
    expect($ids)->not->toContain($centerStudent->id);
});

it('rejects center filter for system survey targeting', function (): void {
    $this->asAdmin();

    $center = Center::factory()->create();

    $response = $this->getJson(
        '/api/v1/admin/surveys/target-students?scope_type='.SurveyScopeType::System->value.'&center_id='.$center->id,
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

it('rejects center scope type on system target students route', function (): void {
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
