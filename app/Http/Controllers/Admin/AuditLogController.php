<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Filters\Admin\AuditLogFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AuditLogs\ListAuditLogsRequest;
use App\Http\Resources\Admin\AuditLogResource;
use App\Models\Center;
use App\Models\User;
use App\Services\Audit\AuditLogQueryService;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogQueryService $queryService
    ) {}

    /**
     * List audit logs.
     */
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

        $filters = $request->filters();
        $paginator = $this->queryService->paginate($admin, $filters);

        return $this->buildListResponse($paginator->items(), $paginator->currentPage(), $paginator->perPage(), $paginator->total(), $paginator->lastPage());
    }

    /**
     * List audit logs for a center.
     */
    public function centerIndex(ListAuditLogsRequest $request, Center $center): JsonResponse
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

        $requestFilters = $request->filters();
        $filters = new AuditLogFilters(
            page: $requestFilters->page,
            perPage: $requestFilters->perPage,
            centerId: (int) $center->id,
            courseId: $requestFilters->courseId,
            entityType: $requestFilters->entityType,
            entityId: $requestFilters->entityId,
            action: $requestFilters->action,
            userId: $requestFilters->userId,
            dateFrom: $requestFilters->dateFrom,
            dateTo: $requestFilters->dateTo
        );

        $paginator = $this->queryService->paginate($admin, $filters);

        return $this->buildListResponse($paginator->items(), $paginator->currentPage(), $paginator->perPage(), $paginator->total(), $paginator->lastPage());
    }

    /**
     * @param  array<int, mixed>  $items
     */
    private function buildListResponse(
        array $items,
        int $page,
        int $perPage,
        int $total,
        int $lastPage
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => 'Audit logs retrieved successfully',
            'data' => AuditLogResource::collection($items),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ]);
    }
}
