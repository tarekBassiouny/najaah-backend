<?php

declare(strict_types=1);

namespace App\Services\Settings\Contracts;

use App\Filters\Admin\SystemSettingFilters;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SystemSettingServiceInterface
{
    /** @return LengthAwarePaginator<SystemSetting> */
    public function list(SystemSettingFilters $filters): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): SystemSetting;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SystemSetting $setting, array $data, ?User $actor = null): SystemSetting;

    public function delete(SystemSetting $setting, ?User $actor = null): void;
}
