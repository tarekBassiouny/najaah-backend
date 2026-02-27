<?php

declare(strict_types=1);

namespace App\Services\Videos;

use App\Enums\MediaSourceType;
use App\Exceptions\DomainException;
use App\Models\Center;
use App\Models\User;
use App\Models\Video;
use App\Services\Bunny\BunnyEmbedTokenService;
use App\Services\Centers\CenterScopeService;
use App\Support\ErrorCodes;

class AdminVideoPreviewService
{
    private const BUNNY_EMBED_BASE_URL = 'https://iframe.mediadelivery.net/embed';

    public function __construct(
        private readonly BunnyEmbedTokenService $embedTokenService,
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @return array{embed_url:string,expires_at:string|null,expires:int|null}
     */
    public function generate(User $admin, Center $center, Video $video): array
    {
        if (! $this->centerScopeService->isSystemSuperAdmin($admin)) {
            $this->centerScopeService->assertAdminCenterId($admin, $center->id);
            $this->centerScopeService->assertAdminCenterId($admin, $video->center_id);
        }

        if ($video->source_type === MediaSourceType::Url) {
            $sourceUrl = $video->source_url;
            if (! is_string($sourceUrl) || $sourceUrl === '') {
                $this->deny(ErrorCodes::VIDEO_NOT_READY, 'Video URL is not available for preview.', 422);
            }

            return [
                'embed_url' => $this->resolveEmbedUrlFromSource($sourceUrl),
                'expires_at' => null,
                'expires' => null,
            ];
        }

        $videoUuid = $video->source_id;
        if (! is_string($videoUuid) || $videoUuid === '') {
            $this->deny(ErrorCodes::VIDEO_NOT_READY, 'Video is not ready for preview.', 422);
        }

        $libraryId = $video->library_id ?? (is_numeric(config('bunny.api.library_id')) ? (int) config('bunny.api.library_id') : null);
        if ($libraryId === null) {
            $this->deny(ErrorCodes::VIDEO_NOT_READY, 'Video library is not configured.', 422);
        }

        $ttl = $this->resolveEmbedTokenTtl();
        $tokenData = $this->embedTokenService->generateForVideo($videoUuid, $ttl);
        $expires = (int) $tokenData['expires'];

        return [
            'embed_url' => $this->buildEmbedUrl((string) $libraryId, $videoUuid, (string) $tokenData['token'], $expires),
            'expires_at' => now()->setTimestamp($expires)->toIso8601String(),
            'expires' => $expires,
        ];
    }

    private function buildEmbedUrl(string $libraryId, string $videoUuid, string $token, int $expires): string
    {
        return sprintf(
            '%s/%s/%s?token=%s&expires=%d',
            self::BUNNY_EMBED_BASE_URL,
            $libraryId,
            $videoUuid,
            $token,
            $expires
        );
    }

    private function resolveEmbedTokenTtl(): int
    {
        $ttl = (int) config('bunny.embed_token_ttl', 240);
        if ($ttl <= 0) {
            $ttl = 240;
        }

        $max = (int) config('playback.embed_token_ttl_max', 300);
        $min = (int) config('playback.embed_token_ttl_min', 180);

        return min($max, max($min, $ttl));
    }

    private function resolveEmbedUrlFromSource(string $sourceUrl): string
    {
        $parts = parse_url($sourceUrl);
        if (! is_array($parts)) {
            return $sourceUrl;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');
        $query = (string) ($parts['query'] ?? '');

        $youtubeId = $this->extractYouTubeId($host, $path, $query);
        if ($youtubeId !== null) {
            return 'https://www.youtube.com/embed/'.$youtubeId;
        }

        $vimeoId = $this->extractVimeoId($host, $path);
        if ($vimeoId !== null) {
            return 'https://player.vimeo.com/video/'.$vimeoId;
        }

        return $sourceUrl;
    }

    private function extractYouTubeId(string $host, string $path, string $query): ?string
    {
        if (! str_contains($host, 'youtube.com') && ! str_contains($host, 'youtu.be')) {
            return null;
        }

        $trimmedPath = trim($path, '/');

        if (str_contains($host, 'youtu.be') && $trimmedPath !== '') {
            $id = explode('/', $trimmedPath)[0] ?? '';

            return $id !== '' ? $id : null;
        }

        if ($trimmedPath !== '') {
            $segments = explode('/', $trimmedPath);
            if (($segments[0] ?? '') === 'embed' && isset($segments[1]) && $segments[1] !== '') {
                return $segments[1];
            }

            if (($segments[0] ?? '') === 'shorts' && isset($segments[1]) && $segments[1] !== '') {
                return $segments[1];
            }
        }

        parse_str($query, $queryParams);
        $videoId = $queryParams['v'] ?? null;

        return is_string($videoId) && $videoId !== '' ? $videoId : null;
    }

    private function extractVimeoId(string $host, string $path): ?string
    {
        if (! str_contains($host, 'vimeo.com')) {
            return null;
        }

        $trimmedPath = trim($path, '/');
        if ($trimmedPath === '') {
            return null;
        }

        $segments = explode('/', $trimmedPath);
        $candidate = end($segments);

        if (! is_string($candidate) || $candidate === '' || ! ctype_digit($candidate)) {
            return null;
        }

        return $candidate;
    }

    /**
     * @return never
     */
    private function deny(string $code, string $message, int $status): void
    {
        throw new DomainException($message, $code, $status);
    }
}
