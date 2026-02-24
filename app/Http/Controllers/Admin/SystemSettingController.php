<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AdminAuthenticates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\ListSystemSettingsRequest;
use App\Http\Requests\Admin\Settings\StoreSystemSettingRequest;
use App\Http\Requests\Admin\Settings\UpdateSystemSettingRequest;
use App\Http\Resources\Admin\Settings\SystemSettingResource;
use App\Models\SystemSetting;
use App\Services\Settings\Contracts\SystemSettingServiceInterface;
use Illuminate\Http\JsonResponse;

class SystemSettingController extends Controller
{
    use AdminAuthenticates;

    public function __construct(
        private readonly SystemSettingServiceInterface $systemSettingService
    ) {}

    /**
     * List system settings.
     */
    public function index(ListSystemSettingsRequest $request): JsonResponse
    {
        $filters = $request->filters();
        $paginator = $this->systemSettingService->list($filters);

        return response()->json([
            'success' => true,
            'data' => SystemSettingResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Create a system setting.
     */
    public function store(StoreSystemSettingRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $setting = $this->systemSettingService->create($data, $admin);

        return response()->json([
            'success' => true,
            'data' => new SystemSettingResource($setting),
        ], 201);
    }

    /**
     * Show a system setting.
     */
    public function show(SystemSetting $systemSetting): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new SystemSettingResource($systemSetting),
        ]);
    }

    /**
     * Update a system setting.
     */
    public function update(UpdateSystemSettingRequest $request, SystemSetting $systemSetting): JsonResponse
    {
        $admin = $this->requireAdmin();
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $setting = $this->systemSettingService->update($systemSetting, $data, $admin);

        return response()->json([
            'success' => true,
            'data' => new SystemSettingResource($setting),
        ]);
    }

    /**
     * Delete a system setting.
     */
    public function destroy(SystemSetting $systemSetting): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->systemSettingService->delete($systemSetting, $admin);

        return response()->json([
            'success' => true,
            'data' => null,
        ], 200);
    }
}
