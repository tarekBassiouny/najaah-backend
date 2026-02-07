<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Roles;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\Roles\PermissionResource;
use App\Services\Permissions\Contracts\PermissionServiceInterface;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    public function __construct(
        private readonly PermissionServiceInterface $permissionService
    ) {}

    /**
     * List permissions.
     */
    public function index(): JsonResponse
    {
        $permissions = $this->permissionService->list();

        return response()->json([
            'success' => true,
            'data' => PermissionResource::collection($permissions),
        ]);
    }
}
