<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Models\Center;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

trait AdminTestHelper
{
    public ?string $adminToken;

    public ?Center $adminCenter = null;

    private function ensureSuperAdminRole(): Role
    {
        $role = Role::firstOrCreate(['slug' => 'super_admin'], [
            'name' => 'super admin',
            'name_translations' => [
                'en' => 'super admin',
                'ar' => 'super admin',
            ],
            'description_translations' => [
                'en' => 'Full system administrator',
                'ar' => 'Full system administrator',
            ],
        ]);

        $permissions = [
            'admin.manage',
            'role.manage',
            'permission.view',
            'course.manage',
            'course.publish',
            'section.manage',
            'video.manage',
            'video.upload',
            'video.playback.override',
            'pdf.manage',
            'enrollment.manage',
            'center.manage',
            'settings.manage',
            'audit.view',
            'device_change.manage',
            'extra_view.manage',
            'video_access.manage',
            'instructor.manage',
            'student.manage',
            'survey.manage',
        ];

        $permissionIds = [];
        foreach ($permissions as $name) {
            $permission = Permission::firstOrCreate(['name' => $name], [
                'description' => 'Permission: '.$name,
            ]);
            $permissionIds[] = $permission->id;
        }

        $role->permissions()->sync($permissionIds);

        return $role;
    }

    /**
     * Create and authenticate a system admin (center_id = null).
     *
     * Sets up system API key header automatically.
     */
    public function asAdmin(): User
    {
        $this->ensureSystemApiKey();

        /** @var User $admin */
        $admin = User::factory()->create([
            'password' => 'secret123',
            'is_student' => false,
            'center_id' => null,
        ]);

        $role = $this->ensureSuperAdminRole();
        $admin->roles()->syncWithoutDetaching([$role->id]);

        $this->adminToken = (string) Auth::guard('admin')->attempt([
            'email' => $admin->email,
            'password' => 'secret123',
            'is_student' => false,
        ]);

        if ($this->adminToken !== null && $this->adminToken !== '') {
            $this->withHeaders([
                'Authorization' => 'Bearer '.$this->adminToken,
                'X-Api-Key' => Config::get('services.system_api_key'),
            ]);
        }

        $this->adminCenter = null;

        return $admin;
    }

    /**
     * Create and authenticate a center admin.
     *
     * Sets up center API key header automatically.
     */
    public function asCenterAdmin(?Center $center = null): User
    {
        $center ??= Center::factory()->create();
        $this->adminCenter = $center;

        /** @var User $admin */
        $admin = User::factory()->create([
            'password' => 'secret123',
            'is_student' => false,
            'center_id' => $center->id,
        ]);

        $role = $this->ensureSuperAdminRole();
        $admin->roles()->syncWithoutDetaching([$role->id]);

        $this->adminToken = (string) Auth::guard('admin')->attempt([
            'email' => $admin->email,
            'password' => 'secret123',
            'is_student' => false,
        ]);

        if ($this->adminToken !== null && $this->adminToken !== '') {
            $this->withHeaders([
                'Authorization' => 'Bearer '.$this->adminToken,
                'X-Api-Key' => $center->api_key,
            ]);
        }

        return $admin;
    }

    private function ensureSystemApiKey(): void
    {
        $systemKey = (string) Config::get('services.system_api_key', '');
        if ($systemKey === '') {
            Config::set('services.system_api_key', 'system-test-key');
        }
    }

    /**
     * Get admin headers with appropriate API key.
     *
     * Uses center API key if authenticated as center admin,
     * otherwise uses system API key.
     */
    public function adminHeaders(array $extra = []): array
    {
        $this->ensureSystemApiKey();

        $apiKey = $this->adminCenter !== null
            ? $this->adminCenter->api_key
            : Config::get('services.system_api_key');

        return array_merge([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->adminToken,
            'X-Api-Key' => $apiKey,
        ], $extra);
    }
}
