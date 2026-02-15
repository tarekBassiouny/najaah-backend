<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Enums\UserDeviceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\UpdateProfileRequest;
use App\Http\Resources\Admin\StudentProfileResource;
use App\Http\Resources\Mobile\StudentUserResource;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Auth\Contracts\JwtServiceInterface;
use App\Services\Students\StudentProfileQueryService;
use App\Services\Students\StudentService;
use App\Support\AuditActions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MeController extends Controller
{
    public function __construct(
        private readonly StudentService $studentService,
        private readonly JwtServiceInterface $jwtService,
        private readonly AuditLogService $auditLogService,
        private readonly StudentProfileQueryService $studentProfileQueryService
    ) {}

    public function profile(): JsonResponse
    {
        $user = request()->user();
        if ($user === null) {
            $this->deny();
        }

        $user->load([
            'center',
            'roles',
            'devices' => fn ($q) => $q->where('status', UserDeviceStatus::Active->value),
        ]);
        $user->setRelation('activeDevice', $user->devices->first());

        return response()->json([
            'success' => true,
            'data' => new StudentUserResource($user),
        ]);
    }

    public function profileDetails(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return $this->deny();
        }

        $resolvedCenterId = $request->attributes->get('resolved_center_id');
        $this->studentProfileQueryService->assertMatchesResolvedCenterScope(
            $user,
            is_numeric($resolvedCenterId) ? (int) $resolvedCenterId : null
        );

        $this->studentProfileQueryService->load($user);

        return response()->json([
            'success' => true,
            'data' => new StudentProfileResource($user),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = request()->user();
        if ($user === null) {
            $this->deny();
        }

        $data = $request->validated();
        $updated = $this->studentService->update($user, [
            'name' => $data['name'],
        ]);

        return response()->json([
            'success' => true,
            'data' => new StudentUserResource($updated),
        ]);
    }

    public function logout(): JsonResponse
    {
        $user = request()->user();

        if ($user === null) {
            $this->deny();
        }

        $this->auditLogService->log($user, $user, AuditActions::STUDENT_LOGOUT);

        $this->jwtService->revokeCurrent();

        return response()->json([
            'success' => true,
        ]);
    }

    private function deny(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED',
                'message' => 'Authentication required.',
            ],
        ], Response::HTTP_UNAUTHORIZED);
    }
}
