<?php

declare(strict_types=1);

namespace App\Services\Bunny;

use App\Models\Video;
use App\Models\VideoUploadSession;
use App\Services\Videos\VideoUploadService;
use Illuminate\Validation\ValidationException;

class BunnyWebhookService
{
    public function __construct(private readonly BunnyWebhookVerifier $verifier) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, ?string $signature): void
    {
        if (! $this->verifier->verify($payload, $signature)) {
            abort(401, 'Invalid signature');
        }

        $event = $payload['Event'] ?? null;
        $videoGuid = $payload['VideoGuid'] ?? null;

        if (! is_string($event) || ! is_string($videoGuid)) {
            abort(400, 'Invalid payload');
        }

        $session = VideoUploadSession::where('bunny_upload_id', $videoGuid)->latest()->first();
        $videos = Video::where('source_id', $videoGuid)->get();

        $status = $this->mapStatus($event);
        $errorMessage = $payload['ErrorMessage'] ?? null;

        if ($session !== null) {
            $update = [
                'upload_status' => $status,
            ];

            if ($status === VideoUploadService::STATUS_FAILED && is_string($errorMessage)) {
                $update['error_message'] = $errorMessage;
            }

            $session->update($update);
        }

        foreach ($videos as $video) {
            if ($status === VideoUploadService::STATUS_READY) {
                $video->encoding_status = VideoUploadService::STATUS_READY;
                $video->lifecycle_status = 2;
                $video->save();
            } elseif ($status === VideoUploadService::STATUS_FAILED) {
                if ((int) $video->encoding_status === VideoUploadService::STATUS_READY) {
                    continue;
                }
                $video->encoding_status = 0;
                $video->lifecycle_status = 0;
                $video->save();
            }
        }
    }

    private function mapStatus(string $event): int
    {
        $map = [
            'UploadStarted' => VideoUploadService::STATUS_UPLOADING,
            'UploadFinished' => VideoUploadService::STATUS_PROCESSING,
            'EncodingStarted' => VideoUploadService::STATUS_PROCESSING,
            'EncodingFinished' => VideoUploadService::STATUS_READY,
            'EncodingFailed' => VideoUploadService::STATUS_FAILED,
        ];

        if (! isset($map[$event])) {
            throw ValidationException::withMessages([
                'event' => ['Unsupported Bunny event.'],
            ]);
        }

        return $map[$event];
    }
}
