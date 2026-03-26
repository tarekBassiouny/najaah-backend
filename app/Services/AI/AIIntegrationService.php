<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Enums\AIContentJobStatus;
use App\Enums\AIProvider;
use App\Exceptions\DomainException;
use App\Models\AICenterProviderSetting;
use App\Models\AIContentJob;
use App\Models\AIProviderConfig;
use App\Models\Center;
use App\Models\User;
use App\Services\AI\Contracts\AIIntegrationServiceInterface;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Services\Settings\PolicySettingsService;
use App\Support\AuditActions;
use App\Support\ErrorCodes;
use Illuminate\Support\Carbon;

final class AIIntegrationService implements AIIntegrationServiceInterface
{
    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly AuditLogService $auditLogService,
        private readonly PolicySettingsService $policySettingsService
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listSystemProviders(): array
    {
        $defaults = $this->providerDefaultsMap();
        $rows = AIProviderConfig::query()
            ->whereIn('provider_key', array_keys($defaults))
            ->get()
            ->keyBy('provider_key');

        $providers = [];
        foreach ($defaults as $providerKey => $default) {
            /** @var AIProviderConfig|null $row */
            $row = $rows->get($providerKey);
            $providers[] = $this->buildSystemProviderPayload($providerKey, $default, $row);
        }

        return $providers;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSystemProvider(string $providerKey, array $data, User $actor): AIProviderConfig
    {
        $normalizedKey = $this->normalizeProviderKey($providerKey);
        $default = $this->providerDefault($normalizedKey);

        /** @var AIProviderConfig|null $existing */
        $existing = AIProviderConfig::query()
            ->where('provider_key', $normalizedKey)
            ->first();

        $models = $this->normalizeModelList($data['models'] ?? $existing?->models ?? $default['models']);
        $defaultModel = $this->resolveDefaultModel(
            $models,
            is_string($data['default_model'] ?? null) ? $data['default_model'] : null,
            is_string($existing?->default_model) ? $existing->default_model : (string) $default['default_model']
        );

        $attributes = [
            'display_name' => is_string($data['display_name'] ?? null) && trim($data['display_name']) !== ''
                ? trim($data['display_name'])
                : (is_string($existing?->display_name) ? $existing->display_name : (string) $default['label']),
            'is_enabled' => array_key_exists('is_enabled', $data)
                ? (bool) $data['is_enabled']
                : ($existing?->is_enabled ?? true),
            'default_model' => $defaultModel,
            'models' => $models,
            'updated_by' => $actor->id,
        ];

        if (! $existing instanceof AIProviderConfig) {
            $attributes['provider_key'] = $normalizedKey;
            $attributes['created_by'] = $actor->id;
        }

        if (array_key_exists('api_key', $data) && is_string($data['api_key'])) {
            $attributes['api_key'] = trim($data['api_key']) !== '' ? trim($data['api_key']) : null;
        } elseif (($data['reset_api_key'] ?? false) === true) {
            $attributes['api_key'] = null;
        }

        /** @var AIProviderConfig $provider */
        $provider = AIProviderConfig::query()->updateOrCreate(
            ['provider_key' => $normalizedKey],
            $attributes
        );

        $this->auditLogService->log($actor, $provider, AuditActions::SYSTEM_SETTING_UPDATED, [
            'provider_key' => $normalizedKey,
            'models_count' => count($models),
            'is_enabled' => $provider->is_enabled,
        ]);

        return $provider->fresh() ?? $provider;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listCenterProviders(Center $center): array
    {
        $systemProviders = collect($this->listSystemProviders())->keyBy('key');
        $settings = AICenterProviderSetting::query()
            ->where('center_id', $center->id)
            ->get()
            ->keyBy('provider_key');

        $providers = [];
        foreach ($systemProviders as $providerKey => $systemProvider) {
            /** @var AICenterProviderSetting|null $setting */
            $setting = $settings->get($providerKey);
            $providers[] = $this->buildCenterProviderPayload($systemProvider, $setting);
        }

        return $providers;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCenterProvider(Center $center, string $providerKey, array $data, User $actor): AICenterProviderSetting
    {
        $this->centerScopeService->assertAdminSameCenter($actor, $center);
        $this->assertCenterProviderUpdateAllowed($actor, $data);
        $normalizedKey = $this->normalizeProviderKey($providerKey);

        $systemProvider = collect($this->listSystemProviders())
            ->firstWhere('key', $normalizedKey);

        if (! is_array($systemProvider)) {
            throw new DomainException('AI provider is not supported.', ErrorCodes::NOT_FOUND, 404);
        }

        /** @var AICenterProviderSetting|null $existing */
        $existing = AICenterProviderSetting::query()
            ->where('center_id', $center->id)
            ->where('provider_key', $normalizedKey)
            ->first();

        $systemModels = $this->normalizeModelList($systemProvider['models'] ?? []);
        $requestedAllowedModels = $data['allowed_models'] ?? $existing?->allowed_models;
        $allowedModels = $requestedAllowedModels === null
            ? null
            : $this->normalizeModelList($requestedAllowedModels);

        if (is_array($allowedModels)) {
            $allowedModels = array_values(array_intersect($systemModels, $allowedModels));
        }

        $effectiveModels = is_array($allowedModels) && $allowedModels !== []
            ? $allowedModels
            : $systemModels;

        $defaultModel = $this->resolveDefaultModel(
            $effectiveModels,
            is_string($data['default_model'] ?? null) ? $data['default_model'] : null,
            is_string($existing?->default_model)
                ? $existing->default_model
                : (is_string($systemProvider['default_model'] ?? null) ? $systemProvider['default_model'] : null)
        );

        $limits = $this->normalizeLimits($data['limits'] ?? $existing?->limits ?? []);

        $attributes = [
            'is_enabled' => array_key_exists('is_enabled', $data)
                ? (bool) $data['is_enabled']
                : ($existing?->is_enabled ?? true),
            'allowed_models' => $allowedModels,
            'default_model' => $defaultModel,
            'limits' => $limits,
            'updated_by' => $actor->id,
        ];

        if (! $existing instanceof AICenterProviderSetting) {
            $attributes['created_by'] = $actor->id;
        }

        /** @var AICenterProviderSetting $setting */
        $setting = AICenterProviderSetting::query()->updateOrCreate(
            [
                'center_id' => $center->id,
                'provider_key' => $normalizedKey,
            ],
            $attributes
        );

        $this->auditLogService->log($actor, $setting, AuditActions::CENTER_SETTINGS_UPDATED, [
            'center_id' => $center->id,
            'provider_key' => $normalizedKey,
            'is_enabled' => $setting->is_enabled,
            'models_count' => count($effectiveModels),
        ]);

        return $setting->fresh() ?? $setting;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCenterOptions(Center $center, bool $enabledOnly = false): array
    {
        $this->assertAIContentEnabled($center);

        $providers = $this->listCenterProviders($center);
        if ($enabledOnly) {
            $providers = array_values(array_filter(
                $providers,
                static fn (array $provider): bool => (bool) ($provider['enabled'] ?? false)
                    && (bool) ($provider['configured'] ?? false)
                    && is_array($provider['models'] ?? null)
                    && ($provider['models'] ?? []) !== []
            ));
        }

        $configuredEnabledProviders = array_values(array_filter(
            $providers,
            static fn (array $provider): bool => (bool) ($provider['enabled'] ?? false) && (bool) ($provider['configured'] ?? false)
        ));

        $configuredDefaultProvider = strtolower(trim((string) config('services.ai.provider', AIProvider::OpenAI->value)));
        $defaultProvider = $configuredEnabledProviders[0]['key'] ?? null;
        foreach ($configuredEnabledProviders as $provider) {
            if (($provider['key'] ?? null) === $configuredDefaultProvider) {
                $defaultProvider = $configuredDefaultProvider;
                break;
            }
        }

        return [
            'default_provider' => $defaultProvider,
            'providers' => $providers,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveProviderAndModel(Center $center, ?string $requestedProvider, ?string $requestedModel): array
    {
        $this->assertAIContentEnabled($center);

        $options = $this->getCenterOptions($center, false);

        /** @var array<int, array<string,mixed>> $providers */
        $providers = is_array($options['providers'] ?? null) ? $options['providers'] : [];
        $providersByKey = collect($providers)->keyBy('key');

        $providerKey = strtolower(trim($requestedProvider ?? (string) ($options['default_provider'] ?? '')));
        if ($providerKey === '' || ! $providersByKey->has($providerKey)) {
            throw new DomainException('AI provider is not available for this center.', ErrorCodes::AI_PROVIDER_NOT_AVAILABLE, 422);
        }

        /** @var array<string,mixed> $provider */
        $provider = $providersByKey->get($providerKey);

        if (! (bool) ($provider['enabled'] ?? false)) {
            throw new DomainException('AI provider is disabled for this center.', ErrorCodes::AI_PROVIDER_NOT_AVAILABLE, 422);
        }

        $availableModels = $this->normalizeModelList($provider['models'] ?? []);
        $model = trim((string) ($requestedModel ?? $provider['default_model'] ?? ''));
        if ($model === '' || ! in_array($model, $availableModels, true)) {
            throw new DomainException('AI model is not allowed for this provider.', ErrorCodes::AI_MODEL_NOT_ALLOWED, 422);
        }

        /** @var array<string,int> $limits */
        $limits = is_array($provider['limits'] ?? null) ? $provider['limits'] : $this->normalizeLimits([]);

        return [
            'provider' => $providerKey,
            'model' => $model,
            'models' => $availableModels,
            'limits' => $limits,
            'api_key' => $this->resolveApiKey($providerKey),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function effectiveLimits(Center $center, string $providerKey): array
    {
        $provider = collect($this->listCenterProviders($center))->firstWhere('key', $this->normalizeProviderKey($providerKey));

        if (! is_array($provider)) {
            return $this->normalizeLimits([]);
        }

        return is_array($provider['limits'] ?? null)
            ? $this->normalizeLimits($provider['limits'])
            : $this->normalizeLimits([]);
    }

    public function resolveApiKey(string $providerKey): string
    {
        $normalizedKey = $this->normalizeProviderKey($providerKey);

        /** @var AIProviderConfig|null $providerConfig */
        $providerConfig = AIProviderConfig::query()
            ->where('provider_key', $normalizedKey)
            ->first(['id', 'api_key']);

        $dbKey = $providerConfig?->api_key;

        if (is_string($dbKey) && trim($dbKey) !== '') {
            return trim($dbKey);
        }

        $serviceKey = trim((string) config(sprintf('services.%s.api_key', $normalizedKey), ''));
        if ($serviceKey !== '') {
            return $serviceKey;
        }

        return '';
    }

    public function assertCanCreateJob(
        Center $center,
        string $providerKey,
        int $inputChars,
        int $estimatedInputTokens,
        int $estimatedOutputTokens
    ): void {
        $normalizedProviderKey = $this->normalizeProviderKey($providerKey);
        $limits = $this->effectiveLimits($center, $normalizedProviderKey);

        $this->assertInputSize($limits, $inputChars);
        $this->assertJobCountLimits($center, $normalizedProviderKey, $limits);
        $this->assertConcurrentLimit($center, $normalizedProviderKey, $limits);
        $this->assertTokenLimitsForCreation(
            $center,
            $normalizedProviderKey,
            $limits,
            $estimatedInputTokens,
            $estimatedOutputTokens
        );
    }

    public function assertCanStoreOutput(
        Center $center,
        string $providerKey,
        AIContentJob $job,
        int $outputChars,
        int $actualOutputTokens
    ): void {
        $normalizedProviderKey = $this->normalizeProviderKey($providerKey);
        $limits = $this->effectiveLimits($center, $normalizedProviderKey);

        $maxOutputChars = $limits['max_output_chars'] ?? 0;
        if ($maxOutputChars > 0 && $outputChars > $maxOutputChars) {
            throw new DomainException('Generated output exceeds center output size limit.', ErrorCodes::AI_LIMIT_OUTPUT_TOO_LARGE, 422);
        }

        $reservedOutputTokens = (int) $job->estimated_output_tokens;
        $additionalOutputTokens = max(0, $actualOutputTokens - $reservedOutputTokens);

        if ($additionalOutputTokens <= 0) {
            return;
        }

        $todayStart = Carbon::now()->startOfDay();
        $monthStart = Carbon::now()->startOfMonth();

        $dailyLimit = (int) ($limits['daily_token_limit'] ?? 0);
        if ($dailyLimit > 0) {
            $dailyUsed = $this->tokenUsage($center->id, $normalizedProviderKey, $todayStart);
            if (($dailyUsed + $additionalOutputTokens) > $dailyLimit) {
                throw new DomainException('Center exceeded daily AI token limit.', ErrorCodes::AI_LIMIT_DAILY_TOKENS_EXCEEDED, 422);
            }
        }

        $monthlyLimit = (int) ($limits['monthly_token_limit'] ?? 0);
        if ($monthlyLimit > 0) {
            $monthlyUsed = $this->tokenUsage($center->id, $normalizedProviderKey, $monthStart);
            if (($monthlyUsed + $additionalOutputTokens) > $monthlyLimit) {
                throw new DomainException('Center exceeded monthly AI token limit.', ErrorCodes::AI_LIMIT_MONTHLY_TOKENS_EXCEEDED, 422);
            }
        }
    }

    public function estimateTokensFromChars(int $charCount): int
    {
        if ($charCount <= 0) {
            return 1;
        }

        return max(1, (int) ceil($charCount / 4));
    }

    public function defaultEstimatedOutputTokens(Center $center, string $providerKey): int
    {
        $limits = $this->effectiveLimits($center, $providerKey);
        $maxOutputChars = (int) ($limits['max_output_chars'] ?? 0);

        if ($maxOutputChars > 0) {
            return $this->estimateTokensFromChars($maxOutputChars);
        }

        $fallback = (int) config('ai.limits.default_output_token_estimate', 1200);

        return max(1, $fallback);
    }

    /**
     * @param  array<string,mixed>  $systemProvider
     * @return array<string,mixed>
     */
    private function buildCenterProviderPayload(array $systemProvider, ?AICenterProviderSetting $setting): array
    {
        $systemModels = $this->normalizeModelList($systemProvider['models'] ?? []);
        $allowedModels = is_array($setting?->allowed_models)
            ? $this->normalizeModelList($setting->allowed_models)
            : null;

        $models = is_array($allowedModels)
            ? array_values(array_intersect($systemModels, $allowedModels))
            : $systemModels;

        $defaultModel = $this->resolveDefaultModel(
            $models,
            is_string($setting?->default_model) ? $setting->default_model : null,
            is_string($systemProvider['default_model'] ?? null) ? $systemProvider['default_model'] : null
        );

        $isSystemEnabled = (bool) ($systemProvider['is_enabled'] ?? false);
        $isCenterEnabled = $setting?->is_enabled ?? true;
        $limits = $this->normalizeLimits($setting?->limits ?? []);

        return [
            'key' => (string) ($systemProvider['key'] ?? ''),
            'label' => (string) ($systemProvider['label'] ?? ''),
            'enabled' => $isSystemEnabled && $isCenterEnabled,
            'configured' => (bool) ($systemProvider['configured'] ?? false),
            'default_model' => $defaultModel,
            'models' => $models,
            'limits' => $limits,
            'system_enabled' => $isSystemEnabled,
            'center_enabled' => $isCenterEnabled,
            'has_custom_api_key' => (bool) ($systemProvider['has_custom_api_key'] ?? false),
        ];
    }

    /**
     * @param  array<string,mixed>  $default
     * @return array<string,mixed>
     */
    private function buildSystemProviderPayload(string $providerKey, array $default, ?AIProviderConfig $row): array
    {
        $models = $this->normalizeModelList($row?->models ?? $default['models']);
        $defaultModel = $this->resolveDefaultModel(
            $models,
            is_string($row?->default_model) ? $row->default_model : null,
            is_string($default['default_model'] ?? null) ? $default['default_model'] : null
        );

        $configured = trim($this->resolveApiKey($providerKey)) !== '';

        return [
            'key' => $providerKey,
            'label' => is_string($row?->display_name) && trim($row->display_name) !== ''
                ? $row->display_name
                : $this->localizedProviderLabel($providerKey, (string) $default['label']),
            'is_enabled' => $row?->is_enabled ?? true,
            'default_model' => $defaultModel,
            'models' => $models,
            'configured' => $configured,
            'has_custom_api_key' => is_string($row?->api_key) && trim($row->api_key) !== '',
        ];
    }

    /**
     * @return array<string, array<string,mixed>>
     */
    private function providerDefaultsMap(): array
    {
        $configured = config('ai.providers');
        $providers = [];

        foreach (AIProvider::values() as $providerKey) {
            $providerConfig = $configured[$providerKey] ?? [];
            if (! is_array($providerConfig)) {
                continue;
            }

            $providers[$providerKey] = [
                'label' => is_string($providerConfig['label'] ?? null) ? $providerConfig['label'] : ucfirst($providerKey),
                'default_model' => is_string($providerConfig['default_model'] ?? null) ? $providerConfig['default_model'] : null,
                'models' => is_array($providerConfig['models'] ?? null) ? $providerConfig['models'] : [],
            ];
        }

        return $providers;
    }

    /**
     * @return array<string,mixed>
     */
    private function providerDefault(string $providerKey): array
    {
        $defaults = $this->providerDefaultsMap();
        $default = $defaults[$providerKey] ?? null;

        if (! is_array($default)) {
            throw new DomainException('AI provider is not supported.', ErrorCodes::NOT_FOUND, 404);
        }

        return $default;
    }

    private function localizedProviderLabel(string $providerKey, string $fallback): string
    {
        $label = __('settings.ai.providers.'.$providerKey);

        return is_string($label) && $label !== 'settings.ai.providers.'.$providerKey
            ? $label
            : $fallback;
    }

    private function normalizeProviderKey(string $providerKey): string
    {
        $normalized = strtolower(trim($providerKey));

        if (! in_array($normalized, AIProvider::values(), true)) {
            throw new DomainException('AI provider is not supported.', ErrorCodes::NOT_FOUND, 404);
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertCenterProviderUpdateAllowed(User $actor, array $data): void
    {
        if ($this->centerScopeService->isSystemSuperAdmin($actor)) {
            return;
        }

        $allowedKeys = ['default_model'];
        $forbiddenKeys = array_diff(array_keys($data), $allowedKeys);

        if ($forbiddenKeys === []) {
            return;
        }

        throw new DomainException(
            'Only system admin can manage AI provider availability, model allowlists, and limits.',
            ErrorCodes::FORBIDDEN,
            403
        );
    }

    private function assertAIContentEnabled(Center $center): void
    {
        if ($this->policySettingsService->isFeatureEnabled($center, 'ai_content')) {
            return;
        }

        throw new DomainException(
            'AI content is disabled for this center.',
            ErrorCodes::FORBIDDEN,
            403
        );
    }

    /**
     * @return list<string>
     */
    private function normalizeModelList(mixed $models): array
    {
        if (! is_array($models)) {
            return [];
        }

        $normalized = [];
        foreach ($models as $model) {
            if (! is_string($model)) {
                continue;
            }

            $trimmed = trim($model);
            if ($trimmed === '') {
                continue;
            }

            $normalized[] = $trimmed;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param  list<string>  $models
     */
    private function resolveDefaultModel(array $models, ?string $preferred, ?string $fallback): ?string
    {
        $candidate = trim((string) $preferred);
        if ($candidate !== '' && in_array($candidate, $models, true)) {
            return $candidate;
        }

        $fallbackCandidate = trim((string) $fallback);
        if ($fallbackCandidate !== '' && in_array($fallbackCandidate, $models, true)) {
            return $fallbackCandidate;
        }

        return $models[0] ?? null;
    }

    /**
     * @param  array<string, int>  $limits
     */
    private function assertInputSize(array $limits, int $inputChars): void
    {
        $maxInputChars = (int) ($limits['max_input_chars'] ?? 0);
        if ($maxInputChars > 0 && $inputChars > $maxInputChars) {
            throw new DomainException('Input content exceeds center AI input size limit.', ErrorCodes::AI_LIMIT_INPUT_TOO_LARGE, 422);
        }
    }

    /**
     * @param  array<string, int>  $limits
     */
    private function assertJobCountLimits(Center $center, string $providerKey, array $limits): void
    {
        $todayStart = Carbon::now()->startOfDay();
        $monthStart = Carbon::now()->startOfMonth();

        $dailyLimit = (int) ($limits['daily_job_limit'] ?? 0);
        if ($dailyLimit > 0) {
            $dailyCount = AIContentJob::query()
                ->where('center_id', $center->id)
                ->where('ai_provider', $providerKey)
                ->where('created_at', '>=', $todayStart)
                ->count();

            if ($dailyCount >= $dailyLimit) {
                throw new DomainException('Center exceeded daily AI job limit.', ErrorCodes::AI_LIMIT_DAILY_JOBS_EXCEEDED, 422);
            }
        }

        $monthlyLimit = (int) ($limits['monthly_job_limit'] ?? 0);
        if ($monthlyLimit > 0) {
            $monthlyCount = AIContentJob::query()
                ->where('center_id', $center->id)
                ->where('ai_provider', $providerKey)
                ->where('created_at', '>=', $monthStart)
                ->count();

            if ($monthlyCount >= $monthlyLimit) {
                throw new DomainException('Center exceeded monthly AI job limit.', ErrorCodes::AI_LIMIT_MONTHLY_JOBS_EXCEEDED, 422);
            }
        }
    }

    /**
     * @param  array<string, int>  $limits
     */
    private function assertConcurrentLimit(Center $center, string $providerKey, array $limits): void
    {
        $maxConcurrent = (int) ($limits['max_concurrent_jobs'] ?? 0);
        if ($maxConcurrent <= 0) {
            return;
        }

        $concurrent = AIContentJob::query()
            ->where('center_id', $center->id)
            ->where('ai_provider', $providerKey)
            ->where('status', AIContentJobStatus::Processing->value)
            ->count();

        if ($concurrent >= $maxConcurrent) {
            throw new DomainException('Center exceeded concurrent AI jobs limit.', ErrorCodes::AI_LIMIT_CONCURRENT_JOBS_EXCEEDED, 422);
        }
    }

    /**
     * @param  array<string, int>  $limits
     */
    private function assertTokenLimitsForCreation(
        Center $center,
        string $providerKey,
        array $limits,
        int $estimatedInputTokens,
        int $estimatedOutputTokens
    ): void {
        $todayStart = Carbon::now()->startOfDay();
        $monthStart = Carbon::now()->startOfMonth();
        $incomingTokenEstimate = max(0, $estimatedInputTokens) + max(0, $estimatedOutputTokens);

        $dailyLimit = (int) ($limits['daily_token_limit'] ?? 0);
        if ($dailyLimit > 0) {
            $dailyUsed = $this->tokenUsage($center->id, $providerKey, $todayStart);
            if (($dailyUsed + $incomingTokenEstimate) > $dailyLimit) {
                throw new DomainException('Center exceeded daily AI token limit.', ErrorCodes::AI_LIMIT_DAILY_TOKENS_EXCEEDED, 422);
            }
        }

        $monthlyLimit = (int) ($limits['monthly_token_limit'] ?? 0);
        if ($monthlyLimit > 0) {
            $monthlyUsed = $this->tokenUsage($center->id, $providerKey, $monthStart);
            if (($monthlyUsed + $incomingTokenEstimate) > $monthlyLimit) {
                throw new DomainException('Center exceeded monthly AI token limit.', ErrorCodes::AI_LIMIT_MONTHLY_TOKENS_EXCEEDED, 422);
            }
        }
    }

    private function tokenUsage(int $centerId, string $providerKey, Carbon $from): int
    {
        /** @var int|float|string|null $sum */
        $sum = AIContentJob::query()
            ->where('center_id', $centerId)
            ->where('ai_provider', $providerKey)
            ->where('created_at', '>=', $from)
            ->selectRaw('COALESCE(SUM(COALESCE(estimated_input_tokens, 0) + COALESCE(estimated_output_tokens, 0)), 0) as total_tokens')
            ->value('total_tokens');

        return (int) $sum;
    }

    /**
     * @return array<string, int>
     */
    private function normalizeLimits(mixed $rawLimits): array
    {
        $defaults = config('ai.limits', []);
        $merged = is_array($defaults) ? $defaults : [];

        if (is_array($rawLimits)) {
            foreach ($rawLimits as $key => $value) {
                if (! is_string($key) || ! array_key_exists($key, $merged)) {
                    continue;
                }

                if (! is_numeric($value)) {
                    continue;
                }

                $merged[$key] = max(0, (int) $value);
            }
        }

        $normalized = [];
        foreach ($merged as $key => $value) {
            if (! is_string($key) || ! is_numeric($value)) {
                continue;
            }

            $normalized[$key] = max(0, (int) $value);
        }

        return $normalized;
    }
}
