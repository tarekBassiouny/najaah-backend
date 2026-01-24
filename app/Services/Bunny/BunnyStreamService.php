<?php

declare(strict_types=1);

namespace App\Services\Bunny;

use App\Services\Logging\LogContextResolver;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use ToshY\BunnyNet\BunnyHttpClient;
use ToshY\BunnyNet\Exception\Client\BunnyHttpClientResponseException;
use ToshY\BunnyNet\Exception\Client\BunnyJsonException;
use ToshY\BunnyNet\Model\Api\Stream\ManageVideos\CreateVideo;
use ToshY\BunnyNet\Model\Api\Stream\ManageVideos\GetVideo;

class BunnyStreamService
{
    private BunnyHttpClient $client;

    private string $apiUrlValue;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $apiUrl,
        private readonly string $libraryId,
        ?ClientInterface $httpClient = null,
    ) {
        $this->apiUrlValue = rtrim($this->apiUrl, '/');
        $this->client = new BunnyHttpClient(
            client: $httpClient ?? new Client,
            apiKey: $this->apiKey,
            baseUrl: $this->normalizeBaseUrl($this->apiUrl),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{id:string, upload_url:string, tus_upload_url:string, presigned_headers:array<string, string|int>, raw:array<string, mixed>, library_id:int}
     *
     * @throws BunnyJsonException
     * @throws ClientExceptionInterface
     */
    public function createVideo(array $payload, ?int $libraryId = null, ?int $expiresInSeconds = null): array
    {
        $libraryIdValue = $libraryId ?? (is_numeric($this->libraryId) ? (int) $this->libraryId : null);
        if ($libraryIdValue === null) {
            Log::error('Missing Bunny Stream library ID.', $this->resolveLogContext([
                'source' => 'api',
            ]));
            throw new \RuntimeException('Missing Bunny library ID.');
        }

        try {
            $response = $this->client->request(
                new CreateVideo($libraryIdValue, $payload)
            );
            $data = $this->decodeResponse($response->getContents());
        } catch (BunnyHttpClientResponseException $bunnyHttpClientResponseException) {
            Log::warning('Bunny create video request failed.', $this->resolveLogContext([
                'source' => 'api',
                'library_id' => $libraryIdValue,
                'error' => $bunnyHttpClientResponseException->getMessage(),
            ]));
            $data = $this->decodeResponse($bunnyHttpClientResponseException->getMessage());
        }

        $id = $data['guid'] ?? $data['id'] ?? null;

        if (! is_string($id) || $id === '') {
            Log::error('Bunny create video returned invalid id.', $this->resolveLogContext([
                'source' => 'api',
                'library_id' => $libraryIdValue,
            ]));
            throw new \RuntimeException('Failed to create Bunny video.');
        }

        $uploadUrl = $data['upload_url']
            ?? $this->apiUrlValue.sprintf('/library/%d/videos/%s', $libraryIdValue, $id);

        // Generate presigned headers for TUS resumable upload (no API key exposure)
        $presignedHeaders = $this->generatePresignedHeaders($libraryIdValue, $id, $expiresInSeconds);

        return [
            'id' => $id,
            'upload_url' => $uploadUrl,
            'tus_upload_url' => 'https://video.bunnycdn.com/tusupload',
            'presigned_headers' => $presignedHeaders,
            'raw' => $data,
            'library_id' => $libraryIdValue,
        ];
    }

    /**
     * Generate presigned headers for TUS resumable uploads.
     * This allows clients to upload directly without the API key.
     *
     * @return array{AuthorizationSignature:string, AuthorizationExpire:int, VideoId:string, LibraryId:int}
     */
    public function generatePresignedHeaders(int $libraryId, string $videoId, ?int $expiresInSeconds = null): array
    {
        $expiresInSeconds = $expiresInSeconds ?? (int) config('uploads.video_upload_token_ttl_seconds', 10800);
        $expirationTime = time() + $expiresInSeconds;

        // Signature formula: sha256(library_id + api_key + expiration_time + video_id)
        $signaturePayload = $libraryId.$this->apiKey.$expirationTime.$videoId;
        $signature = hash('sha256', $signaturePayload);

        return [
            'AuthorizationSignature' => $signature,
            'AuthorizationExpire' => $expirationTime,
            'VideoId' => $videoId,
            'LibraryId' => $libraryId,
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws BunnyJsonException
     * @throws ClientExceptionInterface
     */
    public function getVideo(string $videoGuid, ?int $libraryId = null): array
    {
        $libraryIdValue = $libraryId ?? (is_numeric($this->libraryId) ? (int) $this->libraryId : null);
        if ($libraryIdValue === null) {
            Log::error('Missing Bunny Stream library ID.', $this->resolveLogContext([
                'source' => 'api',
                'video_id' => $videoGuid,
            ]));
            throw new \RuntimeException('Missing Bunny library ID.');
        }

        try {
            $response = $this->client->request(
                new GetVideo($libraryIdValue, $videoGuid)
            );

            return $this->decodeResponse($response->getContents());
        } catch (BunnyHttpClientResponseException $bunnyHttpClientResponseException) {
            Log::warning('Bunny get video request failed.', $this->resolveLogContext([
                'source' => 'api',
                'library_id' => $libraryIdValue,
                'video_id' => $videoGuid,
                'error' => $bunnyHttpClientResponseException->getMessage(),
            ]));

            return $this->decodeResponse($bunnyHttpClientResponseException->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(mixed $contents): array
    {
        if (is_array($contents)) {
            return $contents;
        }

        if (is_string($contents)) {
            $decoded = json_decode($contents, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function normalizeBaseUrl(string $apiUrl): string
    {
        $apiUrl = rtrim($apiUrl, '/');
        $host = parse_url($apiUrl, PHP_URL_HOST);

        if (is_string($host) && $host !== '') {
            return $host;
        }

        return ltrim(preg_replace('/^https?:\\/\\//', '', $apiUrl) ?? $apiUrl, '/');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function resolveLogContext(array $overrides = []): array
    {
        return app(LogContextResolver::class)->resolve($overrides);
    }
}
