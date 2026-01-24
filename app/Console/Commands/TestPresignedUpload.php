<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Storage\Contracts\StorageServiceInterface;
use App\Services\Storage\StoragePathResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestPresignedUpload extends Command
{
    protected $signature = 'test:presigned-upload {--center=1 : Center ID for path generation}';

    protected $description = 'Test presigned URL upload flow with a sample PDF';

    public function handle(StorageServiceInterface $storage, StoragePathResolver $pathResolver): int
    {
        $centerId = (int) $this->option('center');
        $filename = sprintf('test_%s.pdf', uniqid());
        $objectKey = $pathResolver->pdf($centerId, $filename);

        $this->info('Testing presigned upload for: '.$objectKey);
        $this->newLine();

        // Step 1: Generate presigned URL
        $this->info('Step 1: Generating presigned upload URL...');
        $ttl = 600;

        try {
            $uploadUrl = $storage->temporaryUploadUrl($objectKey, $ttl, 'application/pdf');
            $this->line('Upload URL: '.$uploadUrl);
            $this->newLine();
        } catch (\Throwable $throwable) {
            $this->error('Failed to generate presigned URL: '.$throwable->getMessage());

            return self::FAILURE;
        }

        // Parse and display URL components
        $parsed = parse_url($uploadUrl);
        parse_str($parsed['query'] ?? '', $queryParams);
        $expiresParam = $this->normalizeQueryParam($queryParams['X-Amz-Expires'] ?? 'N/A');
        $signatureParam = $this->normalizeQueryParam($queryParams['X-Amz-Signature'] ?? '');

        $this->info('URL Components:');
        $this->table(
            ['Parameter', 'Value'],
            [
                ['Host', $parsed['host'] ?? 'N/A'],
                ['Path', $parsed['path'] ?? 'N/A'],
                ['Algorithm', $queryParams['X-Amz-Algorithm'] ?? 'N/A'],
                ['Expires', $expiresParam.' seconds'],
                ['SignedHeaders', $queryParams['X-Amz-SignedHeaders'] ?? 'N/A'],
                ['Signature', substr($signatureParam, 0, 16).'...'],
            ]
        );
        $this->newLine();

        // Step 2: Create test PDF content
        $this->info('Step 2: Creating test PDF content...');
        $testContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000009 00000 n\n0000000058 00000 n\n0000000115 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n190\n%%EOF";
        $contentSize = strlen($testContent);
        $this->line(sprintf('Test content size: %d bytes', $contentSize));
        $this->newLine();

        // Step 3: Upload using presigned URL
        $this->info('Step 3: Uploading to presigned URL...');

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/pdf',
                'Content-Length' => (string) $contentSize,
            ])
                ->withBody($testContent, 'application/pdf')
                ->put($uploadUrl);

            if ($response->successful()) {
                $this->info('Upload successful! Status: '.$response->status());
            } else {
                $this->error('Upload failed! Status: '.$response->status());
                $this->line('Response: '.$response->body());

                return self::FAILURE;
            }
        } catch (\Throwable $throwable) {
            $this->error('Upload request failed: '.$throwable->getMessage());

            return self::FAILURE;
        }

        $this->newLine();

        // Step 4: Verify file exists
        $this->info('Step 4: Verifying file exists in storage...');

        if ($storage->exists($objectKey)) {
            $this->info('File verified: '.$objectKey);
        } else {
            $this->warn('File not found in storage (may need a moment to propagate)');
        }

        $this->newLine();

        // Step 5: Generate download URL
        $this->info('Step 5: Generating download URL...');
        $downloadUrl = $storage->temporaryUrl($objectKey, 300);
        $this->line('Download URL: '.$downloadUrl);
        $this->newLine();

        // Provide curl example
        $this->info('Manual curl test command:');
        $this->line(sprintf("curl -X PUT '%s' \\", $uploadUrl));
        $this->line("  -H 'Content-Type: application/pdf' \\");
        $this->line("  --data-binary '@/path/to/your/file.pdf'");
        $this->newLine();

        $this->info('Test completed successfully!');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>|string  $value
     */
    private function normalizeQueryParam(array|string $value): string
    {
        if (is_array($value)) {
            return $value[0] ?? '';
        }

        return $value;
    }
}
