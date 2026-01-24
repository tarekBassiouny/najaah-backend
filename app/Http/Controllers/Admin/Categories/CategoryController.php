<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Categories;

use App\Http\Controllers\Concerns\AdminAuthenticates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Categories\ListCategoriesRequest;
use App\Http\Requests\Admin\Categories\StoreCategoryRequest;
use App\Http\Requests\Admin\Categories\UpdateCategoryRequest;
use App\Http\Resources\Admin\Categories\CategoryResource;
use App\Models\Category;
use App\Models\Center;
use App\Services\Centers\CenterScopeService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    use AdminAuthenticates;

    public function __construct(private readonly CenterScopeService $centerScopeService) {}

    public function index(ListCategoriesRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->centerScopeService->assertAdminCenterId($admin, (int) $center->id);

        $perPage = (int) $request->integer('per_page', 15);
        /** @var array<string, mixed> $filters */
        $filters = $request->validated();

        $query = Category::query()
            ->where('center_id', $center->id)
            ->orderBy('order_index')
            ->orderByDesc('created_at');

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (isset($filters['parent_id']) && is_numeric($filters['parent_id'])) {
            $query->where('parent_id', (int) $filters['parent_id']);
        }

        if (isset($filters['search']) && is_string($filters['search'])) {
            $term = trim($filters['search']);
            if ($term !== '') {
                $query->where('title_translations', 'like', '%'.$term.'%');
            }
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

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

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
        ], 201);
    }

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

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
        ]);
    }

    public function destroy(Center $center, Category $category): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->centerScopeService->assertAdminSameCenter($admin, $category);
        $this->assertCategoryBelongsToCenter($center, $category);

        $category->delete();

        return response()->json([
            'success' => true,
            'data' => null,
        ], 204);
    }

    private function assertCategoryBelongsToCenter(Center $center, Category $category): void
    {
        if ((int) $category->center_id !== (int) $center->id) {
            $this->notFound('Category not found.');
        }
    }
}
