<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Exceptions\DomainException;
use App\Filters\Admin\SystemSettingFilters;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Settings\Contracts\SystemSettingServiceInterface;
use App\Support\AuditActions;
use App\Support\ErrorCodes;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SystemSettingService implements SystemSettingServiceInterface
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @return LengthAwarePaginator<SystemSetting>
     */
    public function list(SystemSettingFilters $filters): LengthAwarePaginator
    {
        $query = SystemSetting::query()
            ->orderByDesc('created_at');

        if ($filters->search !== null) {
            $query->where('key', 'like', '%'.$filters->search.'%');
        }

        if ($filters->isPublic !== null) {
            $query->where('is_public', $filters->isPublic);
        }

        return $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): SystemSetting
    {
        $this->assertSystemAdminScope($actor);

        $setting = SystemSetting::create($data);

        $this->auditLogService->log($actor, $setting, AuditActions::SYSTEM_SETTING_CREATED);

        return $setting->fresh() ?? $setting;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SystemSetting $setting, array $data, ?User $actor = null): SystemSetting
    {
        $this->assertSystemAdminScope($actor);
        $setting->update($data);

        $this->auditLogService->log($actor, $setting, AuditActions::SYSTEM_SETTING_UPDATED, [
            'updated_fields' => array_keys($data),
        ]);

        return $setting->fresh() ?? $setting;
    }

    public function delete(SystemSetting $setting, ?User $actor = null): void
    {
        $this->assertSystemAdminScope($actor);
        $setting->delete();

        $this->auditLogService->log($actor, $setting, AuditActions::SYSTEM_SETTING_DELETED);
    }

    private function assertSystemAdminScope(?User $actor): void
    {
        if (! $actor instanceof User || ! $this->centerScopeService->isSystemSuperAdmin($actor)) {
            throw new DomainException('System scope access is required.', ErrorCodes::FORBIDDEN, 403);
        }
    }
}
