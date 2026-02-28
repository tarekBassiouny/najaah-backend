<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Evolution\EvolutionWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvolutionWebhookController extends Controller
{
    public function __construct(private readonly EvolutionWebhookService $service) {}

    public function handle(Request $request): JsonResponse
    {
        try {
            $this->service->handle($request, $request->all());
        } catch (\RuntimeException $exception) {
            if ($exception->getMessage() !== 'Evolution webhook secret is invalid.') {
                return response()->json([
                    'success' => false,
                    'message' => 'Evolution webhook processing failed.',
                ], 500);
            }

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 401);
        } catch (\Throwable $throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Evolution webhook processing failed.',
            ], 500);
        }

        return response()->json(['success' => true]);
    }
}
