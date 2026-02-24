<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Centers;

use App\Actions\Admin\Centers\CreateCenterAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Centers\BulkDeleteCentersRequest;
use App\Http\Requests\Admin\Centers\BulkRestoreCentersRequest;
use App\Http\Requests\Admin\Centers\BulkUpdateCenterFeaturedRequest;
use App\Http\Requests\Admin\Centers\BulkUpdateCenterStatusRequest;
use App\Http\Requests\Admin\Centers\BulkUpdateCenterTierRequest;
use App\Http\Requests\Admin\Centers\ListCenterOptionsRequest;
use App\Http\Requests\Admin\Centers\ListCentersRequest;
use App\Http\Requests\Admin\Centers\StoreCenterRequest;
use App\Http\Requests\Admin\Centers\UpdateCenterRequest;
use App\Http\Requests\Admin\Centers\UpdateCenterStatusRequest;
use App\Http\Resources\Admin\Centers\CenterOptionResource;
use App\Http\Resources\Admin\Centers\CenterResource;
use App\Http\Resources\Admin\Users\AdminUserResource;
use App\Models\Center;
use App\Models\User;
use App\Services\Centers\Contracts\CenterServiceInterface;
use Illuminate\Http\JsonResponse;

class CenterController extends Controller
{
    public function __construct(
        private readonly CenterServiceInterface $centerService
    ) {}

    /**
     * List centers.
     */
    public function index(ListCentersRequest $request): JsonResponse
    {
        $filters = $request->filters();
        $paginator = $this->centerService->listAdmin($filters);

        return response()->json([
            'success' => true,
            'message' => 'Centers retrieved successfully',
            'data' => CenterResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * List center options for dropdowns.
     */
    public function options(ListCenterOptionsRequest $request): JsonResponse
    {
        $filters = $request->filters();
        $paginator = $this->centerService->listAdminOptions($filters);

        return response()->json([
            'data' => CenterOptionResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Create a center.
     */
    public function store(StoreCenterRequest $request, CreateCenterAction $action): JsonResponse
    {
        $admin = $request->user();
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $result = $action->execute($data, $admin instanceof User ? $admin : null);

        return response()->json([
            'success' => true,
            'data' => [
                'center' => new CenterResource($result['center']),
                'owner' => new AdminUserResource($result['owner']),
                'email_sent' => $result['email_sent'],
            ],
        ], 201);
    }

    /**
     * Show a center.
     */
    public function show(int $center): JsonResponse
    {
        $center = Center::with('setting')->find($center);

        if ($center === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Center not found',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Center retrieved successfully',
            'data' => new CenterResource($center),
        ]);
    }

    /**
     * Update a center.
     */
    public function update(UpdateCenterRequest $request, int $center): JsonResponse
    {
        $center = Center::find($center);

        if ($center === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Center not found',
                ],
            ], 404);
        }

        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $admin = $request->user();
        $updated = $this->centerService->update($center, $data, $admin instanceof User ? $admin : null);

        return response()->json([
            'success' => true,
            'message' => 'Center updated successfully',
            'data' => new CenterResource($updated),
        ]);
    }

    /**
     * Update center active status.
     */
    public function updateStatus(UpdateCenterStatusRequest $request, int $center): JsonResponse
    {
        $centerModel = Center::find($center);

        if (! $centerModel instanceof Center) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Center not found',
                ],
            ], 404);
        }

        /** @var array{status:int} $data */
        $data = $request->validated();
        $admin = $request->user();
        $updated = $this->centerService->update(
            $centerModel,
            ['status' => (int) $data['status']],
            $admin instanceof User ? $admin : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Center status updated successfully',
            'data' => new CenterResource($updated),
        ]);
    }

    /**
     * Bulk update center statuses.
     */
    public function bulkUpdateStatus(BulkUpdateCenterStatusRequest $request): JsonResponse
    {
        /** @var array{status:int,center_ids:array<int,int>} $data */
        $data = $request->validated();
        $requestedIds = $this->uniqueCenterIds($data['center_ids']);
        $targetStatus = (int) $data['status'];
        $centers = Center::query()
            ->whereIn('id', $requestedIds)
            ->get()
            ->keyBy('id');

        $updated = [];
        $skipped = [];
        $failed = [];
        $admin = $request->user();

        foreach ($requestedIds as $centerId) {
            $centerModel = $centers->get($centerId);
            if (! $centerModel instanceof Center) {
                $failed[] = [
                    'center_id' => $centerId,
                    'reason' => 'Center not found.',
                ];

                continue;
            }

            $currentStatus = is_object($centerModel->status)
                ? (int) $centerModel->status->value
                : (is_numeric($centerModel->status) ? (int) $centerModel->status : null);

            if ($currentStatus === $targetStatus) {
                $skipped[] = $centerId;

                continue;
            }

            $updated[] = $this->centerService->update(
                $centerModel,
                ['status' => $targetStatus],
                $admin instanceof User ? $admin : null
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk center status update processed',
            'data' => [
                'counts' => [
                    'total' => count($requestedIds),
                    'updated' => count($updated),
                    'skipped' => count($skipped),
                    'failed' => count($failed),
                ],
                'updated' => CenterResource::collection($updated),
                'skipped' => $skipped,
                'failed' => $failed,
            ],
        ]);
    }

    /**
     * Bulk update center featured flags.
     */
    public function bulkUpdateFeatured(BulkUpdateCenterFeaturedRequest $request): JsonResponse
    {
        /** @var array{is_featured:bool,center_ids:array<int,int>} $data */
        $data = $request->validated();
        $requestedIds = $this->uniqueCenterIds($data['center_ids']);
        $targetFeatured = (bool) $data['is_featured'];
        $centers = Center::query()
            ->whereIn('id', $requestedIds)
            ->get()
            ->keyBy('id');

        $updated = [];
        $skipped = [];
        $failed = [];
        $admin = $request->user();

        foreach ($requestedIds as $centerId) {
            $centerModel = $centers->get($centerId);
            if (! $centerModel instanceof Center) {
                $failed[] = [
                    'center_id' => $centerId,
                    'reason' => 'Center not found.',
                ];

                continue;
            }

            if ((bool) $centerModel->is_featured === $targetFeatured) {
                $skipped[] = $centerId;

                continue;
            }

            $updated[] = $this->centerService->update(
                $centerModel,
                ['is_featured' => $targetFeatured],
                $admin instanceof User ? $admin : null
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk center featured update processed',
            'data' => [
                'counts' => [
                    'total' => count($requestedIds),
                    'updated' => count($updated),
                    'skipped' => count($skipped),
                    'failed' => count($failed),
                ],
                'updated' => CenterResource::collection($updated),
                'skipped' => $skipped,
                'failed' => $failed,
            ],
        ]);
    }

    /**
     * Bulk update center tiers.
     */
    public function bulkUpdateTier(BulkUpdateCenterTierRequest $request): JsonResponse
    {
        /** @var array{tier:\App\Enums\CenterTier,center_ids:array<int,int>} $data */
        $data = $request->validated();
        $requestedIds = $this->uniqueCenterIds($data['center_ids']);
        $targetTier = $data['tier'];
        $centers = Center::query()
            ->whereIn('id', $requestedIds)
            ->get()
            ->keyBy('id');

        $updated = [];
        $skipped = [];
        $failed = [];
        $admin = $request->user();

        foreach ($requestedIds as $centerId) {
            $centerModel = $centers->get($centerId);
            if (! $centerModel instanceof Center) {
                $failed[] = [
                    'center_id' => $centerId,
                    'reason' => 'Center not found.',
                ];

                continue;
            }

            if ($centerModel->tier === $targetTier) {
                $skipped[] = $centerId;

                continue;
            }

            $updated[] = $this->centerService->update(
                $centerModel,
                ['tier' => $targetTier],
                $admin instanceof User ? $admin : null
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk center tier update processed',
            'data' => [
                'counts' => [
                    'total' => count($requestedIds),
                    'updated' => count($updated),
                    'skipped' => count($skipped),
                    'failed' => count($failed),
                ],
                'updated' => CenterResource::collection($updated),
                'skipped' => $skipped,
                'failed' => $failed,
            ],
        ]);
    }

    /**
     * Delete a center.
     */
    public function destroy(int $center): JsonResponse
    {
        $center = Center::find($center);

        if ($center === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Center not found',
                ],
            ], 404);
        }

        $admin = request()->user();
        $this->centerService->delete($center, $admin instanceof User ? $admin : null);

        return response()->json([
            'success' => true,
            'message' => 'Center deleted successfully',
            'data' => null,
        ], 200);
    }

    /**
     * Bulk delete centers.
     */
    public function bulkDestroy(BulkDeleteCentersRequest $request): JsonResponse
    {
        /** @var array{center_ids:array<int,int>} $data */
        $data = $request->validated();
        $requestedIds = $this->uniqueCenterIds($data['center_ids']);
        $centers = Center::withTrashed()
            ->whereIn('id', $requestedIds)
            ->get()
            ->keyBy('id');

        $deleted = [];
        $skipped = [];
        $failed = [];
        $admin = $request->user();

        foreach ($requestedIds as $centerId) {
            $centerModel = $centers->get($centerId);
            if (! $centerModel instanceof Center) {
                $failed[] = [
                    'center_id' => $centerId,
                    'reason' => 'Center not found.',
                ];

                continue;
            }

            if ($centerModel->trashed()) {
                $skipped[] = $centerId;

                continue;
            }

            $this->centerService->delete($centerModel, $admin instanceof User ? $admin : null);
            $deleted[] = $centerId;
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk center delete processed',
            'data' => [
                'counts' => [
                    'total' => count($requestedIds),
                    'deleted' => count($deleted),
                    'skipped' => count($skipped),
                    'failed' => count($failed),
                ],
                'deleted' => $deleted,
                'skipped' => $skipped,
                'failed' => $failed,
            ],
        ]);
    }

    /**
     * Restore a center.
     */
    public function restore(int $center): JsonResponse
    {
        $admin = request()->user();
        $restored = $this->centerService->restore($center, $admin instanceof User ? $admin : null);

        if ($restored === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Center not found',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Center restored successfully',
            'data' => new CenterResource($restored),
        ]);
    }

    /**
     * Bulk restore centers.
     */
    public function bulkRestore(BulkRestoreCentersRequest $request): JsonResponse
    {
        /** @var array{center_ids:array<int,int>} $data */
        $data = $request->validated();
        $requestedIds = $this->uniqueCenterIds($data['center_ids']);
        $centers = Center::withTrashed()
            ->whereIn('id', $requestedIds)
            ->get()
            ->keyBy('id');

        $restored = [];
        $skipped = [];
        $failed = [];
        $admin = $request->user();

        foreach ($requestedIds as $centerId) {
            $centerModel = $centers->get($centerId);
            if (! $centerModel instanceof Center) {
                $failed[] = [
                    'center_id' => $centerId,
                    'reason' => 'Center not found.',
                ];

                continue;
            }

            if (! $centerModel->trashed()) {
                $skipped[] = $centerId;

                continue;
            }

            $centerResource = $this->centerService->restore(
                (int) $centerModel->id,
                $admin instanceof User ? $admin : null
            );

            if (! $centerResource instanceof Center) {
                $failed[] = [
                    'center_id' => $centerId,
                    'reason' => 'Center not found.',
                ];

                continue;
            }

            $restored[] = $centerResource;
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk center restore processed',
            'data' => [
                'counts' => [
                    'total' => count($requestedIds),
                    'restored' => count($restored),
                    'skipped' => count($skipped),
                    'failed' => count($failed),
                ],
                'restored' => CenterResource::collection($restored),
                'skipped' => $skipped,
                'failed' => $failed,
            ],
        ]);
    }

    /**
     * @param  array<int, int|string>  $centerIds
     * @return array<int, int>
     */
    private function uniqueCenterIds(array $centerIds): array
    {
        return array_values(array_unique(array_map('intval', $centerIds)));
    }
}
