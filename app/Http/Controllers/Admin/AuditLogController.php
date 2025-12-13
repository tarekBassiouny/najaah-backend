<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuditLogs\ListAuditLogsRequest;
use App\Http\Resources\AuditLogResource;
use App\Models\User;
use App\Services\Audit\AuditLogQueryService;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogQueryService $queryService
    ) {}

    public function index(ListAuditLogsRequest $request): JsonResponse
    {
        /** @var User|null $admin */
        $admin = $request->user();

        if (! $admin instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        $perPage = (int) $request->integer('per_page', 15);
        $filters = $request->only(['entity_type', 'entity_id', 'action', 'user_id', 'date_from', 'date_to']);

        $paginator = $this->queryService->paginate($admin, $perPage, $filters);

        return response()->json([
            'success' => true,
            'message' => 'Audit logs retrieved successfully',
            'data' => AuditLogResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
