<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

use App\Models\AICenterProviderSetting;
use App\Models\AIContentJob;
use App\Models\AIProviderConfig;
use App\Models\Center;
use App\Models\User;

interface AIIntegrationServiceInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function listSystemProviders(): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSystemProvider(string $providerKey, array $data, User $actor): AIProviderConfig;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listCenterProviders(Center $center): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCenterProvider(Center $center, string $providerKey, array $data, User $actor): AICenterProviderSetting;

    /**
     * @return array<string, mixed>
     */
    public function getCenterOptions(Center $center, bool $enabledOnly = false): array;

    /**
     * @return array<string, mixed>
     */
    public function resolveProviderAndModel(Center $center, ?string $requestedProvider, ?string $requestedModel): array;

    /**
     * @return array<string, int>
     */
    public function effectiveLimits(Center $center, string $providerKey): array;

    public function resolveApiKey(string $providerKey): string;

    public function assertCanCreateJob(
        Center $center,
        string $providerKey,
        int $inputChars,
        int $estimatedInputTokens,
        int $estimatedOutputTokens
    ): void;

    public function assertCanStoreOutput(
        Center $center,
        string $providerKey,
        AIContentJob $job,
        int $outputChars,
        int $actualOutputTokens
    ): void;

    public function estimateTokensFromChars(int $charCount): int;

    public function defaultEstimatedOutputTokens(Center $center, string $providerKey): int;
}
