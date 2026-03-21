<?php

declare(strict_types=1);

use App\Enums\AIContentJobStatus;
use App\Enums\AIContentSourceType;
use App\Enums\AIContentTargetType;
use App\Enums\CenterType;
use App\Jobs\ProcessAIContentJob;
use App\Models\AICenterProviderSetting;
use App\Models\AIContentJob;
use App\Models\AIProviderConfig;
use App\Models\Center;
use App\Models\Course;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Helpers\AdminTestHelper;

use function PHPUnit\Framework\assertGreaterThanOrEqual;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('admin', 'ai-content');

it('lists system ai providers', function (): void {
    $this->asAdmin();

    $response = $this->getJson('/api/v1/admin/ai/providers', $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'key',
                    'label',
                    'is_enabled',
                    'default_model',
                    'models',
                    'configured',
                ],
            ],
        ]);

    $count = count((array) $response->json('data'));
    assertGreaterThanOrEqual(3, $count);
});

it('updates system ai provider settings without exposing api key', function (): void {
    $this->asAdmin();

    $response = $this->putJson('/api/v1/admin/ai/providers/openai', [
        'display_name' => 'OpenAI',
        'is_enabled' => true,
        'default_model' => 'gpt-4o-mini',
        'models' => ['gpt-4o-mini', 'gpt-4.1-mini'],
        'api_key' => 'sk-test-key',
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.key', 'openai')
        ->assertJsonPath('data.is_enabled', true)
        ->assertJsonPath('data.default_model', 'gpt-4o-mini');

    $response->assertJsonMissingPath('data.api_key');

    $this->assertDatabaseHas('ai_provider_configs', [
        'provider_key' => 'openai',
        'display_name' => 'OpenAI',
        'is_enabled' => true,
        'default_model' => 'gpt-4o-mini',
    ]);
});

it('system admin updates center ai provider settings and returns effective options', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $admin = $this->asAdmin();

    AIProviderConfig::factory()->create([
        'provider_key' => 'openai',
        'display_name' => 'OpenAI',
        'is_enabled' => true,
        'default_model' => 'gpt-4o-mini',
        'models' => ['gpt-4o-mini', 'gpt-4.1-mini'],
        'api_key' => 'sk-system-key',
    ]);

    $updateResponse = $this->putJson(
        "/api/v1/admin/centers/{$center->id}/ai/providers/openai",
        [
            'is_enabled' => true,
            'allowed_models' => ['gpt-4o-mini'],
            'default_model' => 'gpt-4o-mini',
            'limits' => [
                'daily_job_limit' => 5,
                'max_concurrent_jobs' => 2,
            ],
        ],
        $this->adminHeaders()
    );

    $updateResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.key', 'openai')
        ->assertJsonPath('data.default_model', 'gpt-4o-mini')
        ->assertJsonPath('data.limits.daily_job_limit', 5)
        ->assertJsonPath('data.limits.max_concurrent_jobs', 2);

    $optionsResponse = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/ai/options?enabled_only=true",
        $this->adminHeaders()
    );

    $optionsResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.default_provider', 'openai')
        ->assertJsonPath('data.providers.0.key', 'openai')
        ->assertJsonPath('data.providers.0.enabled', true)
        ->assertJsonPath('data.providers.0.configured', true)
        ->assertJsonPath('data.providers.0.default_model', 'gpt-4o-mini');
});

it('center admin can only update default model for center ai provider settings', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $this->asCenterAdmin($center);

    AIProviderConfig::factory()->create([
        'provider_key' => 'openai',
        'display_name' => 'OpenAI',
        'is_enabled' => true,
        'default_model' => 'gpt-4o-mini',
        'models' => ['gpt-4o-mini', 'gpt-4.1-mini'],
        'api_key' => 'sk-system-key',
    ]);

    $response = $this->putJson(
        "/api/v1/admin/centers/{$center->id}/ai/providers/openai",
        [
            'default_model' => 'gpt-4o-mini',
        ],
        $this->adminHeaders()
    );

    $response->assertOk()
        ->assertJsonPath('data.default_model', 'gpt-4o-mini');
});

it('blocks center ai options when ai content is disabled for the center', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $center->setting()->create([
        'settings' => [
            'features' => [
                'ai_content' => false,
            ],
        ],
    ]);

    $this->asCenterAdmin($center);

    AIProviderConfig::factory()->create([
        'provider_key' => 'openai',
        'display_name' => 'OpenAI',
        'is_enabled' => true,
        'default_model' => 'gpt-4o-mini',
        'models' => ['gpt-4o-mini'],
        'api_key' => 'sk-system-key',
    ]);

    $response = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/ai/options?enabled_only=true",
        $this->adminHeaders()
    );

    $response->assertStatus(403)
        ->assertJsonPath('error.code', 'FORBIDDEN');
});

it('blocks center admin from updating ai limits and provider availability', function (): void {
    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $this->asCenterAdmin($center);

    AIProviderConfig::factory()->create([
        'provider_key' => 'openai',
        'display_name' => 'OpenAI',
        'is_enabled' => true,
        'default_model' => 'gpt-4o-mini',
        'models' => ['gpt-4o-mini', 'gpt-4.1-mini'],
        'api_key' => 'sk-system-key',
    ]);

    $response = $this->putJson(
        "/api/v1/admin/centers/{$center->id}/ai/providers/openai",
        [
            'is_enabled' => false,
            'limits' => [
                'daily_job_limit' => 5,
            ],
        ],
        $this->adminHeaders()
    );

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('blocks ai job creation when daily job limit is exceeded', function (): void {
    Queue::fake();

    $center = Center::factory()->create(['type' => CenterType::Branded]);
    $course = Course::factory()->create(['center_id' => $center->id]);
    $video = Video::factory()->create([
        'center_id' => $center->id,
        'transcript' => 'Lesson transcript',
    ]);
    $course->videos()->attach($video->id, [
        'section_id' => null,
        'order_index' => 1,
        'visible' => true,
        'view_limit_override' => null,
    ]);

    $admin = $this->asCenterAdmin($center);

    AIProviderConfig::factory()->create([
        'provider_key' => 'openai',
        'display_name' => 'OpenAI',
        'is_enabled' => true,
        'default_model' => 'gpt-4o-mini',
        'models' => ['gpt-4o-mini'],
        'api_key' => 'sk-system-key',
    ]);

    AICenterProviderSetting::factory()->create([
        'center_id' => $center->id,
        'provider_key' => 'openai',
        'limits' => [
            'daily_job_limit' => 1,
            'monthly_job_limit' => 100,
            'daily_token_limit' => 999999,
            'monthly_token_limit' => 9999999,
            'max_input_chars' => 999999,
            'max_output_chars' => 999999,
            'max_concurrent_jobs' => 10,
        ],
    ]);

    AIContentJob::factory()->create([
        'center_id' => $center->id,
        'course_id' => $course->id,
        'source_type' => AIContentSourceType::Course,
        'source_id' => $course->id,
        'target_type' => AIContentTargetType::Summary,
        'status' => AIContentJobStatus::Pending,
        'ai_provider' => 'openai',
        'ai_model' => 'gpt-4o-mini',
        'created_by' => $admin->id,
        'created_at' => now(),
    ]);

    $response = $this->postJson(
        "/api/v1/admin/centers/{$center->id}/ai-content/jobs",
        [
            'course_id' => $course->id,
            'source_type' => AIContentSourceType::Video->value,
            'source_id' => $video->id,
            'target_type' => AIContentTargetType::Summary->value,
            'ai_provider' => 'openai',
            'ai_model' => 'gpt-4o-mini',
        ],
        $this->adminHeaders()
    );

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'AI_LIMIT_DAILY_JOBS_EXCEEDED');

    Queue::assertNotPushed(ProcessAIContentJob::class);
});
