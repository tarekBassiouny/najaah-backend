<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminParentResource;
use App\Http\Resources\Admin\AdminParentStudentLinkResource;
use App\Models\Center;
use App\Models\User;
use App\Services\Parents\Contracts\ParentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentController extends Controller
{
    public function __construct(
        private readonly ParentServiceInterface $parentService
    ) {}

    public function centerIndex(Request $request, Center $center): JsonResponse
    {
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', '15');

        $paginator = $this->parentService->listParents(
            (int) $center->id,
            is_string($search) ? $search : null,
            $perPage
        );

        return response()->json([
            'success' => true,
            'data' => AdminParentResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function centerShow(Request $request, Center $center, User $parent): JsonResponse
    {
        $parent->loadCount(['parentLinks as active_links_count' => fn ($q) => $q->active()]);
        $parent->load([
            'parentLinks' => fn ($q) => $q->with(['student:id,name,phone', 'linkedByUser:id,name'])
                ->where('center_id', $center->id)
                ->orderByDesc('created_at'),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'parent' => new AdminParentResource($parent),
                'links' => AdminParentStudentLinkResource::collection($parent->parentLinks),
            ],
        ]);
    }

    public function pendingRequests(Request $request, Center $center): JsonResponse
    {
        $links = $this->parentService->listPendingRequests((int) $center->id);

        return response()->json([
            'success' => true,
            'data' => AdminParentStudentLinkResource::collection($links),
        ]);
    }

    public function studentLinks(Request $request, User $student): JsonResponse
    {
        $links = $this->parentService->listLinksForStudent($student->id);

        return response()->json([
            'success' => true,
            'data' => AdminParentStudentLinkResource::collection($links),
        ]);
    }
}
