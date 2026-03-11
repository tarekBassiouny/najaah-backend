<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AICenterProviderSetting;
use App\Models\Center;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AICenterProviderSetting>
 */
class AICenterProviderSettingFactory extends Factory
{
    protected $model = AICenterProviderSetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'center_id' => Center::factory(),
            'provider_key' => 'openai',
            'is_enabled' => true,
            'allowed_models' => ['gpt-4o-mini'],
            'default_model' => 'gpt-4o-mini',
            'limits' => [
                'daily_job_limit' => 50,
                'monthly_job_limit' => 500,
                'daily_token_limit' => 500000,
                'monthly_token_limit' => 5000000,
                'max_input_chars' => 40000,
                'max_output_chars' => 20000,
                'max_concurrent_jobs' => 5,
            ],
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
