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

    /**
     * Update feature flags for a center (system admin only).
     *
     * @param  array<string, bool>  $features
     */
    public function updateFeatures(User $actor, Center $center, array $features): CenterSetting;
}
