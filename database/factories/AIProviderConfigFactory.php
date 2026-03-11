<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AIProviderConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AIProviderConfig>
 */
class AIProviderConfigFactory extends Factory
{
    protected $model = AIProviderConfig::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider_key' => 'openai',
            'display_name' => 'OpenAI',
            'is_enabled' => true,
            'default_model' => 'gpt-4o-mini',
            'models' => ['gpt-4o-mini', 'gpt-4.1-mini'],
            'api_key' => 'test-api-key',
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
