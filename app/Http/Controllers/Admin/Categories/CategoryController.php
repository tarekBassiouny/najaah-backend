<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Categories;

use App\Http\Controllers\Concerns\AdminAuthenticates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Categories\BulkDeleteCategoriesRequest;
use App\Http\Requests\Admin\Categories\BulkUpdateCategoryStatusRequest;
use App\Http\Requests\Admin\Categories\ListCategoriesRequest;
use App\Http\Requests\Admin\Categories\StoreCategoryRequest;
use App\Http\Requests\Admin\Categories\UpdateCategoryRequest;
use App\Http\Resources\Admin\Categories\CategoryResource;
use App\Models\Category;
use App\Models\Center;
use App\Services\Audit\AuditLogService;
use App\Services\Categories\AdminCategoryQueryService;
use App\Services\Centers\CenterScopeService;
use App\Support\AuditActions;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    use AdminAuthenticates;

    public function __construct(
        private readonly CenterScopeService $centerScopeService,
        private readonly AdminCategoryQueryService $queryService,
        private readonly AuditLogService $auditLogService
    ) {}

    /**
     * List categories.
     */
    public function index(ListCategoriesRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();
        $filters = $request->filters();
        $paginator = $this->queryService->paginate($admin, $center, $filters);

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Create a category.
     */
    public function store(StoreCategoryRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->centerScopeService->assertAdminCenterId($admin, (int) $center->id);
        /** @var array<string, mixed> $data */
        $data = $request->validated();

        if (isset($data['parent_id']) && is_numeric($data['parent_id'])) {
            $parent = Category::find((int) $data['parent_id']);
            if ($parent instanceof Category) {
                $this->centerScopeService->assertAdminSameCenter($admin, $parent);
            }
        }

        $data['center_id'] = $center->id;

        $category = Category::create($data);
        $this->auditLogService->log($admin, $category, AuditActions::CATEGORY_CREATED);

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
        ], 201);
    }

    /**
     * Show a category.
     */
    public function show(Center $center, Category $category): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->centerScopeService->assertAdminSameCenter($admin, $category);
        $this->assertCategoryBelongsToCenter($center, $category);

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Update a category.
     */
    public function update(UpdateCategoryRequest $request, Center $center, Category $category): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->centerScopeService->assertAdminSameCenter($admin, $category);
        $this->assertCategoryBelongsToCenter($center, $category);
        /** @var array<string, mixed> $data */
        $data = $request->validated();

        if (array_key_exists('parent_id', $data) && is_numeric($data['parent_id'])) {
            $parent = Category::find((int) $data['parent_id']);
            if ($parent instanceof Category) {
                $this->centerScopeService->assertAdminSameCenter($admin, $parent);
            }
        }

        $category->update($data);
        $this->auditLogService->log($admin, $category, AuditActions::CATEGORY_UPDATED);

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Delete a category.
     */
    public function destroy(Center $center, Category $category): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->centerScopeService->assertAdminSameCenter($admin, $category);
        $this->assertCategoryBelongsToCenter($center, $category);

        $this->auditLogService->log($admin, $category, AuditActions::CATEGORY_DELETED);
        $category->delete();

        return response()->json([
            'success' => true,
            'data' => null,
        ], 204);
    }

    /**
     * Bulk update category status.
     */
    public function bulkUpdateStatus(BulkUpdateCategoryStatusRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->centerScopeService->assertAdminCenterId($admin, (int) $center->id);

        /** @var array{is_active: bool, category_ids: array<int, int>} $data */
        $data = $request->validated();
        $requestedIds = array_values(array_unique(array_map('intval', $data['category_ids'])));

        $categories = Category::query()
            ->where('center_id', (int) $center->id)
            ->whereIn('id', $requestedIds)
            ->get()
            ->keyBy('id');

        $updated = [];
        $skipped = [];
        $failed = [];

        foreach ($requestedIds as $categoryId) {
            $category = $categories->get($categoryId);

            if (! $category instanceof Category) {
                $failed[] = [
                    'category_id' => $categoryId,
                    'reason' => 'Category not found.',
                ];

                continue;
            }

            if ($category->is_active === $data['is_active']) {
                $skipped[] = $categoryId;

                continue;
            }

            $category->update(['is_active' => $data['is_active']]);
            $this->auditLogService->log($admin, $category, AuditActions::CATEGORY_UPDATED, [
                'is_active' => $data['is_active'],
            ]);
            $updated[] = $category;
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk category status update processed',
            'data' => [
                'counts' => [
                    'total' => count($requestedIds),
                    'updated' => count($updated),
                    'skipped' => count($skipped),
                    'failed' => count($failed),
                ],
                'updated' => CategoryResource::collection($updated),
                'skipped' => $skipped,
                'failed' => $failed,
            ],
        ]);
    }

    /**
     * Bulk delete categories.
     */
    public function bulkDestroy(BulkDeleteCategoriesRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->centerScopeService->assertAdminCenterId($admin, (int) $center->id);

        /** @var array{category_ids: array<int, int>} $data */
        $data = $request->validated();
        $requestedIds = array_values(array_unique(array_map('intval', $data['category_ids'])));

        $categories = Category::query()
            ->where('center_id', (int) $center->id)
            ->whereIn('id', $requestedIds)
            ->get()
            ->keyBy('id');

        $deleted = [];
        $failed = [];

        foreach ($requestedIds as $categoryId) {
            $category = $categories->get($categoryId);

            if (! $category instanceof Category) {
                $failed[] = [
                    'category_id' => $categoryId,
                    'reason' => 'Category not found.',
                ];

                continue;
            }

            $this->auditLogService->log($admin, $category, AuditActions::CATEGORY_DELETED);
            $category->delete();
            $deleted[] = $categoryId;
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk category delete processed',
            'data' => [
                'counts' => [
                    'total' => count($requestedIds),
                    'deleted' => count($deleted),
                    'failed' => count($failed),
                ],
                'deleted' => $deleted,
                'failed' => $failed,
            ],
        ]);
    }

    private function assertCategoryBelongsToCenter(Center $center, Category $category): void
    {
        if ((int) $category->center_id !== (int) $center->id) {
            $this->notFound('Category not found.');
        }
    }
}
