<?php

declare(strict_types=1);

use App\Models\AIProviderConfig;
use App\Models\Center;
use App\Models\CenterSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\AdminTestHelper;

uses(RefreshDatabase::class, AdminTestHelper::class)->group('center-settings', 'admin');

it('returns center settings', function (): void {
    $center = Center::factory()->create();
    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'default_view_limit' => 2,
            'allow_extra_view_requests' => true,
            'pdf_download_permission' => false,
        ],
    ]);

    $this->asAdmin();
    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/settings", $this->adminHeaders());

    $response
        ->assertOk()
        ->assertJsonPath('data.settings.default_view_limit', 2)
        ->assertJsonPath('data.settings.features.ai_content', true)
        ->assertJsonPath('data.page.type', 'system_admin_center_settings')
        ->assertJsonPath('data.sections.features.values.ai_content', true)
        ->assertJsonPath('data.center_id', $center->id);
});

it('updates center settings with partial payload', function (): void {
    $center = Center::factory()->create();
    CenterSetting::factory()->create([
        'center_id' => $center->id,
        'settings' => [
            'default_view_limit' => 2,
            'allow_extra_view_requests' => true,
            'pdf_download_permission' => false,
        ],
    ]);

    $this->asAdmin();
    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/settings", [
        'settings' => [
            'pdf_download_permission' => true,
        ],
    ], $this->adminHeaders());

    $response
        ->assertOk()
        ->assertJsonPath('data.settings.pdf_download_permission', true);

    $this->assertDatabaseHas('center_settings', [
        'center_id' => $center->id,
        'settings->pdf_download_permission' => true,
    ]);
});

it('updates education profile center settings', function (): void {
    $center = Center::factory()->create();

    $this->asAdmin();
    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/settings", [
        'settings' => [
            'education_profile' => [
                'enable_grade' => true,
                'enable_school' => false,
                'enable_college' => true,
                'enable_parent_phone' => true,
                'require_grade' => true,
                'require_school' => false,
                'require_college' => false,
                'require_parent_phone' => true,
            ],
        ],
    ], $this->adminHeaders());

    $response
        ->assertOk()
        ->assertJsonPath('data.settings.education_profile.enable_grade', true)
        ->assertJsonPath('data.settings.education_profile.enable_school', false)
        ->assertJsonPath('data.settings.education_profile.require_grade', true)
        ->assertJsonPath('data.settings.education_profile.require_parent_phone', true);

    $this->assertDatabaseHas('center_settings', [
        'center_id' => $center->id,
        'settings->education_profile->enable_school' => false,
        'settings->education_profile->require_grade' => true,
        'settings->education_profile->require_parent_phone' => true,
    ]);
});

it('rejects unsupported setting keys', function (): void {
    $center = Center::factory()->create();

    $this->asAdmin();
    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/settings", [
        'settings' => [
            'unknown_key' => 5,
        ],
    ], $this->adminHeaders());

    $response->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('rejects unsupported education profile setting keys', function (): void {
    $center = Center::factory()->create();

    $this->asAdmin();
    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/settings", [
        'settings' => [
            'education_profile' => [
                'unknown_toggle' => true,
            ],
        ],
    ], $this->adminHeaders());

    $response->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('requires authentication', function (): void {
    $center = Center::factory()->create();

    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/settings");

    $response->assertStatus(401);
});

it('returns simplified settings payload for center admins', function (): void {
    $center = Center::factory()->create();
    AIProviderConfig::factory()->create([
        'provider_key' => 'openai',
        'display_name' => 'OpenAI',
        'is_enabled' => true,
        'default_model' => 'gpt-4o-mini',
        'models' => ['gpt-4o-mini'],
    ]);

    $this->asCenterAdmin($center);
    $response = $this->getJson("/api/v1/admin/centers/{$center->id}/settings", $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.page.type', 'center_admin_settings')
        ->assertJsonMissingPath('data.features')
        ->assertJsonMissingPath('data.catalog')
        ->assertJsonPath('data.sections.ai.providers.0.managed_by', 'platform');
});

it('localizes center settings summaries and default ai provider labels for center admins', function (): void {
    $center = Center::factory()->create();
    AIProviderConfig::factory()->create([
        'provider_key' => 'gemini',
        'display_name' => '',
        'is_enabled' => true,
        'default_model' => 'gemini-1.5-flash',
        'models' => ['gemini-1.5-flash'],
    ]);

    $this->asCenterAdmin($center);

    $response = $this->getJson(
        "/api/v1/admin/centers/{$center->id}/settings",
        $this->adminHeaders(['X-Locale' => 'ar'])
    );

    $response->assertOk()
        ->assertJsonPath('data.sections.ai.providers.0.label', 'جيميني')
        ->assertJsonPath('data.summaries.0.title', 'موفر الذكاء الاصطناعي مُدار من المنصة')
        ->assertJsonPath(
            'data.summaries.0.message',
            'تم تهيئة جيميني لهذا المركز. تتم إدارة إتاحة الموفر وحدوده من قبل مدير المنصة.'
        );
});

it('localizes grouped center settings validation errors', function (): void {
    $center = Center::factory()->create();

    $this->asCenterAdmin($center);

    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/settings", [
        'features' => [
            'ai_content' => false,
        ],
    ], $this->adminHeaders(['X-Locale' => 'ar']));

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.details.features.0', 'يمكن لمدير النظام فقط إدارة أعلام الميزات.');
});

it('allows system admin to update grouped center settings including features and ai policy', function (): void {
    $center = Center::factory()->create();
    AIProviderConfig::factory()->create([
        'provider_key' => 'openai',
        'display_name' => 'OpenAI',
        'is_enabled' => true,
        'default_model' => 'gpt-4o-mini',
        'models' => ['gpt-4o-mini', 'gpt-4.1-mini'],
    ]);

    $this->asAdmin();

    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/settings", [
        'settings' => [
            'default_view_limit' => 4,
        ],
        'features' => [
            'ai_content' => false,
        ],
        'ai' => [
            'providers' => [
                'openai' => [
                    'is_enabled' => true,
                    'allowed_models' => ['gpt-4o-mini'],
                    'default_model' => 'gpt-4o-mini',
                    'limits' => [
                        'daily_job_limit' => 20,
                    ],
                ],
            ],
        ],
    ], $this->adminHeaders());

    $response->assertOk()
        ->assertJsonPath('data.settings.default_view_limit', 4)
        ->assertJsonPath('data.features.ai_content', false)
        ->assertJsonPath('data.sections.ai.providers.0.default_model', 'gpt-4o-mini')
        ->assertJsonPath('data.sections.ai.providers.0.limits.daily_job_limit', 20);

    $followUp = $this->getJson("/api/v1/admin/centers/{$center->id}/settings", $this->adminHeaders());

    $followUp->assertOk()
        ->assertJsonPath('data.features.ai_content', false)
        ->assertJsonPath('data.settings.features.ai_content', false);

    $this->assertDatabaseHas('center_settings', [
        'center_id' => $center->id,
        'settings->features->ai_content' => false,
    ]);
});

it('blocks center admin from updating feature flags through grouped center settings', function (): void {
    $center = Center::factory()->create();

    $this->asCenterAdmin($center);

    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/settings", [
        'features' => [
            'ai_content' => false,
        ],
    ], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('blocks center admin from updating system-managed ai policy through grouped center settings', function (): void {
    $center = Center::factory()->create();
    AIProviderConfig::factory()->create([
        'provider_key' => 'openai',
        'display_name' => 'OpenAI',
        'is_enabled' => true,
        'default_model' => 'gpt-4o-mini',
        'models' => ['gpt-4o-mini'],
    ]);

    $this->asCenterAdmin($center);

    $response = $this->patchJson("/api/v1/admin/centers/{$center->id}/settings", [
        'ai' => [
            'providers' => [
                'openai' => [
                    'is_enabled' => false,
                ],
            ],
        ],
    ], $this->adminHeaders());

    $response->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR');
});
