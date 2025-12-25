<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\UpdateProfileRequest;
use App\Http\Resources\Mobile\StudentUserResource;
use App\Models\UserDevice;
use App\Services\Auth\Contracts\JwtServiceInterface;
use App\Services\Students\StudentService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MeController extends Controller
{
    public function __construct(
        private readonly StudentService $studentService,
        private readonly JwtServiceInterface $jwtService
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
            'devices' => fn ($q) => $q->where('status', UserDevice::STATUS_ACTIVE),
        ]);
        $user->setRelation('activeDevice', $user->devices->first());

        return response()->json([
            'success' => true,
            'data' => new StudentUserResource($user),
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
