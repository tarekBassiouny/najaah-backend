<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExtraViews\CreateExtraViewRequestRequest;
use App\Http\Resources\ExtraViewRequestResource;
use App\Models\Course;
use App\Models\User;
use App\Models\Video;
use App\Services\Playback\ExtraViewRequestService;
use App\Services\Playback\ViewLimitService;
use Illuminate\Http\JsonResponse;

class ExtraViewRequestController extends Controller
{
    public function __construct(
        private readonly ExtraViewRequestService $service,
        private readonly ViewLimitService $viewLimitService
    ) {}

    public function index(): JsonResponse
    {
        /** @var User|null $student */
        $student = auth('api')->user();

        if (! $student instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        $requests = $student->extraViewRequests()->latest()->get();

        return response()->json([
            'success' => true,
            'data' => ExtraViewRequestResource::collection($requests),
        ]);
    }

    public function store(CreateExtraViewRequestRequest $request, Course $course, Video $video): JsonResponse
    {
        /** @var User|null $student */
        $student = $request->user();

        if (! $student instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401);
        }

        $extraRequest = $this->service->create(
            $student,
            $course,
            $video,
            $request->input('reason')
        );

        $remaining = $this->viewLimitService->remaining($student, $video, $course, null);

        return response()->json([
            'success' => true,
            'message' => 'Extra view request created',
            'data' => new ExtraViewRequestResource($extraRequest),
            'meta' => [
                'remaining_views' => $remaining,
            ],
        ], 201);
    }
}
