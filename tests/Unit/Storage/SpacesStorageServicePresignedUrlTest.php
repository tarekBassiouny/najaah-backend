<?php

declare(strict_types=1);

use App\Services\Storage\SpacesStorageService;
use Aws\CommandInterface;
use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class)->group('storage', 'spaces', 'presigned-url');

beforeEach(function (): void {
    Carbon::setTestNow('2026-01-24 12:00:00');

    config([
        'filesystems.disks.spaces.endpoint' => 'https://nyc3.digitaloceanspaces.com',
        'filesystems.disks.spaces.key' => 'test-access-key',
        'filesystems.disks.spaces.secret' => 'test-secret-key',
        'filesystems.disks.spaces.bucket' => 'test-bucket',
        'filesystems.disks.spaces.region' => 'nyc3',
    ]);
});

afterEach(function (): void {
    Carbon::setTestNow();
    \Mockery::close();
});

it('generates presigned upload url with correct structure using real s3 client', function (): void {
    $disk = \Mockery::mock(Filesystem::class);
    $service = new SpacesStorageService($disk);

    $url = $service->temporaryUploadUrl(
        'centers/1/pdfs/test-file.pdf',
        600,
        'application/pdf'
    );

    // Parse URL components
    $parsed = parse_url($url);
    parse_str($parsed['query'] ?? '', $queryParams);

    // Validate URL structure
    // Note: AWS SDK uses virtual-hosted style URLs where bucket is in the host
    expect($parsed['scheme'])->toBe('https')
        ->and($parsed['host'])->toContain('digitaloceanspaces.com')
        ->and($parsed['path'])->toBe('/centers/1/pdfs/test-file.pdf');

    // Validate AWS Signature V4 query parameters
    expect($queryParams)
        ->toHaveKey('X-Amz-Algorithm')
        ->toHaveKey('X-Amz-Credential')
        ->toHaveKey('X-Amz-Date')
        ->toHaveKey('X-Amz-Expires')
        ->toHaveKey('X-Amz-SignedHeaders')
        ->toHaveKey('X-Amz-Signature');

    // Validate specific values
    expect($queryParams['X-Amz-Algorithm'])->toBe('AWS4-HMAC-SHA256')
        ->and($queryParams['X-Amz-Expires'])->toBe('600')
        ->and($queryParams['X-Amz-SignedHeaders'])->toContain('host');

    // Validate credential format: {access_key}/{date}/{region}/s3/aws4_request
    expect($queryParams['X-Amz-Credential'])->toStartWith('test-access-key/')
        ->and($queryParams['X-Amz-Credential'])->toContain('/s3/aws4_request');

    // Validate signature is hex string (64 chars for SHA256)
    expect($queryParams['X-Amz-Signature'])->toMatch('/^[a-f0-9]{64}$/');
});

it('allows injecting mock s3 client for unit testing', function (): void {
    $disk = \Mockery::mock(Filesystem::class);

    $mockCommand = \Mockery::mock(CommandInterface::class);

    $mockRequest = new Request(
        'PUT',
        new Uri('https://test-bucket.nyc3.digitaloceanspaces.com/centers/1/pdfs/test.pdf?X-Amz-Signature=abc123')
    );

    $mockS3 = \Mockery::mock(S3Client::class);
    $mockS3->shouldReceive('getCommand')
        ->once()
        ->with('PutObject', [
            'Bucket' => 'test-bucket',
            'Key' => 'centers/1/pdfs/test.pdf',
            'ContentType' => 'application/pdf',
        ])
        ->andReturn($mockCommand);

    $mockS3->shouldReceive('createPresignedRequest')
        ->once()
        ->with($mockCommand, '+600 seconds')
        ->andReturn($mockRequest);

    $service = new SpacesStorageService($disk, $mockS3, 'test-bucket');

    $url = $service->temporaryUploadUrl('centers/1/pdfs/test.pdf', 600, 'application/pdf');

    expect($url)->toBe('https://test-bucket.nyc3.digitaloceanspaces.com/centers/1/pdfs/test.pdf?X-Amz-Signature=abc123');
});

it('uses config region instead of hardcoded value', function (): void {
    config(['filesystems.disks.spaces.region' => 'fra1']);

    $disk = \Mockery::mock(Filesystem::class);
    $service = new SpacesStorageService($disk);

    $url = $service->temporaryUploadUrl('test.pdf', 600, 'application/pdf');

    // The region should be used from config (fra1)
    // The credential scope should contain the region
    parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $queryParams);

    expect($queryParams['X-Amz-Credential'])->toContain('/fra1/s3/aws4_request');
});

it('generates valid signed headers', function (): void {
    $disk = \Mockery::mock(Filesystem::class);
    $service = new SpacesStorageService($disk);

    $url = $service->temporaryUploadUrl(
        'centers/1/pdfs/test.pdf',
        600,
        'application/pdf'
    );

    parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $queryParams);

    // Host is always in signed headers for S3 presigned URLs
    expect($queryParams['X-Amz-SignedHeaders'])->toContain('host');
});

it('generates different signatures for different paths', function (): void {
    $disk = \Mockery::mock(Filesystem::class);
    $service = new SpacesStorageService($disk);

    $url1 = $service->temporaryUploadUrl('centers/1/pdfs/file1.pdf', 600, 'application/pdf');
    $url2 = $service->temporaryUploadUrl('centers/1/pdfs/file2.pdf', 600, 'application/pdf');

    parse_str(parse_url($url1, PHP_URL_QUERY) ?? '', $params1);
    parse_str(parse_url($url2, PHP_URL_QUERY) ?? '', $params2);

    expect($params1['X-Amz-Signature'])->not->toBe($params2['X-Amz-Signature']);
});

it('generates different signatures for different TTLs', function (): void {
    $disk = \Mockery::mock(Filesystem::class);
    $service = new SpacesStorageService($disk);

    $url1 = $service->temporaryUploadUrl('centers/1/pdfs/file.pdf', 300, 'application/pdf');
    $url2 = $service->temporaryUploadUrl('centers/1/pdfs/file.pdf', 600, 'application/pdf');

    parse_str(parse_url($url1, PHP_URL_QUERY) ?? '', $params1);
    parse_str(parse_url($url2, PHP_URL_QUERY) ?? '', $params2);

    expect($params1['X-Amz-Expires'])->toBe('300')
        ->and($params2['X-Amz-Expires'])->toBe('600')
        ->and($params1['X-Amz-Signature'])->not->toBe($params2['X-Amz-Signature']);
});

it('throws runtime exception when s3 client fails', function (): void {
    $disk = \Mockery::mock(Filesystem::class);

    $mockCommand = \Mockery::mock(CommandInterface::class);

    $mockS3 = \Mockery::mock(S3Client::class);
    $mockS3->shouldReceive('getCommand')
        ->once()
        ->andReturn($mockCommand);

    $mockS3->shouldReceive('createPresignedRequest')
        ->once()
        ->andThrow(new \Exception('S3 error'));

    $service = new SpacesStorageService($disk, $mockS3, 'test-bucket');

    expect(fn () => $service->temporaryUploadUrl('test.pdf', 600, 'application/pdf'))
        ->toThrow(RuntimeException::class, 'Failed to generate presigned upload URL');
});

it('strips leading slash from path', function (): void {
    $disk = \Mockery::mock(Filesystem::class);
    $service = new SpacesStorageService($disk);

    $url = $service->temporaryUploadUrl(
        '/centers/1/pdfs/test.pdf',
        600,
        'application/pdf'
    );

    $path = parse_url($url, PHP_URL_PATH);

    // Should not have double slashes
    expect($path)->not->toContain('//');
});

it('uses injected bucket instead of config when provided', function (): void {
    $disk = \Mockery::mock(Filesystem::class);

    $mockCommand = \Mockery::mock(CommandInterface::class);

    $mockRequest = new Request(
        'PUT',
        new Uri('https://custom-bucket.nyc3.digitaloceanspaces.com/test.pdf')
    );

    $mockS3 = \Mockery::mock(S3Client::class);
    $mockS3->shouldReceive('getCommand')
        ->once()
        ->with('PutObject', [
            'Bucket' => 'custom-bucket',
            'Key' => 'test.pdf',
            'ContentType' => 'application/pdf',
        ])
        ->andReturn($mockCommand);

    $mockS3->shouldReceive('createPresignedRequest')
        ->once()
        ->andReturn($mockRequest);

    // Inject custom bucket
    $service = new SpacesStorageService($disk, $mockS3, 'custom-bucket');

    $url = $service->temporaryUploadUrl('test.pdf', 600, 'application/pdf');

    expect($url)->toContain('custom-bucket');
});
