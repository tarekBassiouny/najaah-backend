<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Webhooks\BunnyWebhookRequest;
use App\Services\Bunny\BunnyWebhookService;
use Illuminate\Http\JsonResponse;

class BunnyWebhookController extends Controller
{
    public function __construct(private readonly BunnyWebhookService $service) {}

    public function handle(BunnyWebhookRequest $request): JsonResponse
    {
        $this->service->handle($request->validated(), $request->header('Bunny-Signature'));

        return response()->json(['success' => true]);
    }
}
