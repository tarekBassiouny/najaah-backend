<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('admin', 'scope');

function centerScopedSuperAdminHeaders(int $centerId): array
{
    $center = Center::query()->findOrFail($centerId);
    $role = Role::query()->where('slug', 'super_admin')->firstOrFail();

    /** @var User $admin */
    $admin = User::factory()->create([
        'password' => 'secret123',
        'is_student' => false,
        'center_id' => $center->id,
    ]);

    $admin->roles()->syncWithoutDetaching([$role->id]);
    $admin->centers()->syncWithoutDetaching([
        (int) $center->id => ['type' => 'admin'],
    ]);

    $token = (string) Auth::guard('admin')->attempt([
        'email' => $admin->email,
        'password' => 'secret123',
        'is_student' => false,
    ]);

    $systemKey = (string) Config::get('services.system_api_key', '');
    if ($systemKey === '') {
        $systemKey = 'system-test-key';
        Config::set('services.system_api_key', $systemKey);
    }

    return [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '.$token,
        'X-Api-Key' => $systemKey,
    ];
}

it('forbids center-scoped super admin from system-only modules', function (): void {
    $this->asAdmin();

    $center = Center::factory()->create();
    $headers = centerScopedSuperAdminHeaders((int) $center->id);

    $this->getJson('/api/v1/admin/analytics/overview', $headers)
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'PERMISSION_DENIED');
});

it('blocks center route mismatch for center-scoped super admin', function (): void {
    $this->asAdmin();

    $ownedCenter = Center::factory()->create();
    $otherCenter = Center::factory()->create();

    $headers = centerScopedSuperAdminHeaders((int) $ownedCenter->id);

    $this->getJson('/api/v1/admin/centers/'.$otherCenter->id.'/courses', $headers)
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

it('allows system super admin to access center routes', function (): void {
    $this->asAdmin();
    $center = Center::factory()->create();

    $this->getJson('/api/v1/admin/centers/'.$center->id.'/courses', $this->adminHeaders())
        ->assertStatus(200)
        ->assertJsonPath('success', true);
});

it('allows center-scoped super admin to access own center audit logs', function (): void {
    $this->asAdmin();

    $center = Center::factory()->create();
    $headers = centerScopedSuperAdminHeaders((int) $center->id);

    $this->getJson('/api/v1/admin/centers/'.$center->id.'/audit-logs', $headers)
        ->assertStatus(200)
        ->assertJsonPath('success', true);
});

it('blocks center-scoped super admin from other center audit logs', function (): void {
    $this->asAdmin();

    $ownedCenter = Center::factory()->create();
    $otherCenter = Center::factory()->create();
    $headers = centerScopedSuperAdminHeaders((int) $ownedCenter->id);

    $this->getJson('/api/v1/admin/centers/'.$otherCenter->id.'/audit-logs', $headers)
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

it('blocks system routes when request uses a center api key', function (): void {
    $this->asAdmin();

    $center = Center::factory()->create([
        'api_key' => 'center-scope-system-block-key',
    ]);

    $from = now()->subDays(7)->toDateString();
    $to = now()->toDateString();

    $this->getJson(
        "/api/v1/admin/analytics/overview?from={$from}&to={$to}",
        $this->adminHeaders(['X-Api-Key' => $center->api_key])
    )
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

it('blocks center route access when api key center does not match route center', function (): void {
    $this->asAdmin();

    $apiKeyCenter = Center::factory()->create([
        'api_key' => 'center-scope-mismatch-key',
    ]);
    $routeCenter = Center::factory()->create();

    $this->getJson(
        '/api/v1/admin/centers/'.$routeCenter->id.'/courses',
        $this->adminHeaders(['X-Api-Key' => $apiKeyCenter->api_key])
    )
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

it('allows center route access when api key center matches route center', function (): void {
    $this->asAdmin();

    $center = Center::factory()->create([
        'api_key' => 'center-scope-match-key',
    ]);

    $this->getJson(
        '/api/v1/admin/centers/'.$center->id.'/courses',
        $this->adminHeaders(['X-Api-Key' => $center->api_key])
    )
        ->assertStatus(200)
        ->assertJsonPath('success', true);
});

it('returns explicit scope metadata in admin me response', function (): void {
    $this->asAdmin();

    $systemResponse = $this->getJson('/api/v1/admin/auth/me', $this->adminHeaders());
    $systemResponse->assertStatus(200)
        ->assertJsonPath('data.user.scope_type', 'system')
        ->assertJsonPath('data.user.scope_center_id', null)
        ->assertJsonPath('data.user.is_system_super_admin', true)
        ->assertJsonPath('data.user.is_center_super_admin', false);

    $center = Center::factory()->create();
    $centerHeaders = centerScopedSuperAdminHeaders((int) $center->id);

    $centerResponse = $this->getJson('/api/v1/admin/auth/me', $centerHeaders);
    $centerResponse->assertStatus(200)
        ->assertJsonPath('data.user.scope_type', 'center')
        ->assertJsonPath('data.user.scope_center_id', (int) $center->id)
        ->assertJsonPath('data.user.is_system_super_admin', false)
        ->assertJsonPath('data.user.is_center_super_admin', true);
});
