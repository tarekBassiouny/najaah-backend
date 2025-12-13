<?php

declare(strict_types=1);

namespace App\Services\Settings\Contracts;

use App\Models\Center;
use App\Models\CenterSetting;

interface CenterSettingsServiceInterface
{
    /**
     * @param  array<string, mixed>  $settings
     */
    public function update(Center $center, array $settings): CenterSetting;

    public function get(Center $center): CenterSetting;
}
