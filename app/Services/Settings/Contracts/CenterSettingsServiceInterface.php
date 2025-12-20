<?php

declare(strict_types=1);

namespace App\Services\Settings\Contracts;

use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\User;

interface CenterSettingsServiceInterface
{
    /**
     * @param  array<string, mixed>  $settings
     */
    public function update(User $actor, Center $center, array $settings): CenterSetting;

    public function get(User $actor, Center $center): CenterSetting;
}
