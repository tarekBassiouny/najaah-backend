<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\StoreEnrollmentRequest;
use App\Models\Center;
use App\Models\Course;
use App\Models\User;
use App\Services\Requests\RequestException;
use App\Services\Requests\RequestService;
use Illuminate\Http\JsonResponse;

class EnrollmentRequestController extends Controller
{
    public function __construct(private readonly RequestService $requestService) {}

    public function store(StoreEnrollmentRequest $request, Center $center, Course $course): JsonResponse
    {
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

        try {
            $this->requestService->createEnrollmentRequest(
                $student,
                $center,
                $course,
                $request->input('reason')
            );
        } catch (RequestException $requestException) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $requestException->errorCode(),
                    'message' => $requestException->getMessage(),
                ],
            ], $requestException->status());
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
