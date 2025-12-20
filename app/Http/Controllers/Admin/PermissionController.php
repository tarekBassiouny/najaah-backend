<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Permissions\ListPermissionsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\PermissionResource;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    public function index(ListPermissionsAction $action): JsonResponse
    {
        $permissions = $action->execute();

        return response()->json([
            'success' => true,
            'data' => PermissionResource::collection($permissions),
        ]);
    }
}
