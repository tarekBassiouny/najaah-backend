<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\RedeemVideoAccessCodeRequest;
use App\Http\Requests\Mobile\RedeemVideoCodeRequest;
use App\Http\Requests\Mobile\ValidateVideoCodeRequest;
use App\Models\User;
use App\Models\VideoCodeRedemption;
use App\Services\VideoAccess\Contracts\VideoApprovalCodeServiceInterface;
use App\Services\VideoAccess\Contracts\VideoCodeRedemptionServiceInterface;
use App\Services\VideoAccess\VideoCodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoAccessCodeController extends Controller
{
    public function __construct(
        private readonly VideoApprovalCodeServiceInterface $service,
        private readonly VideoCodeRedemptionServiceInterface $batchCodeService,
        private readonly VideoCodeGenerator $codeGenerator
    ) {}

    /**
     * Redeem an enrollment-based video access code.
     */
    public function redeem(RedeemVideoAccessCodeRequest $request): JsonResponse
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

        $access = $this->service->redeem($student, (string) $request->input('code'));

        return response()->json([
            'success' => true,
            'message' => 'Video unlocked successfully.',
            'data' => [
                'id' => $access->id,
                'video_id' => $access->video_id,
                'course_id' => $access->course_id,
                'granted_at' => $access->granted_at,
            ],
        ]);
    }

    /**
     * Redeem a batch video code (for video_code access model).
     */
    public function redeemBatchCode(RedeemVideoCodeRequest $request): JsonResponse
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

        $result = $this->batchCodeService->redeemCode($student, (string) $request->input('code'));

        $videoAccess = $result['video_access'];
        $batch = $result['batch'];

        $batch->loadMissing(['video', 'course']);

        $video = $batch->video;
        $course = $batch->course;

        return response()->json([
            'success' => true,
            'message' => 'Code redeemed successfully',
            'data' => [
                'redemption_id' => $result['redemption']->id,
                'video_id' => $videoAccess->video_id,
                'video_title' => $video->translate('title'),
                'course_id' => $videoAccess->course_id,
                'course_title' => $course->translate('title'),
                'center_id' => $batch->center_id,
                'view_limit' => $batch->view_limit_per_code,
                'total_view_limit' => $videoAccess->total_view_limit,
                'batch_code' => $batch->batch_code,
                'sequence_number' => $result['sequence'],
                'redeemed_at' => $result['redeemed_at']->toIso8601String(),
            ],
        ]);
    }

    /**
     * Validate a batch video code without redeeming it.
     * Always returns 200 with valid=true/false per frontend guide.
     */
    public function validateBatchCode(ValidateVideoCodeRequest $request): JsonResponse
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

        $result = $this->batchCodeService->validateCode($student, (string) $request->input('code'));

        // Always return 200 with valid flag per frontend guide
        if (! $result['valid']) {
            return response()->json([
                'success' => true,
                'data' => [
                    'valid' => false,
                    'reason' => $result['reason'],
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Code is valid',
            'data' => [
                'valid' => true,
                'video_id' => $result['video_id'],
                'video_title' => $result['video_title'],
                'course_id' => $result['course_id'],
                'course_title' => $result['course_title'],
                'center_id' => $result['center_id'],
                'view_limit' => $result['view_limit'],
                'can_redeem' => $result['can_redeem'],
            ],
        ]);
    }

    /**
     * List the student's redeemed batch codes.
     */
    public function myRedemptions(Request $request): JsonResponse
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

        /** @var array{page?:int,per_page?:int,course_id?:int} $validated */
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'course_id' => ['sometimes', 'integer', 'min:1'],
        ]);

        $page = array_key_exists('page', $validated) ? $request->integer('page') : 1;
        $perPage = array_key_exists('per_page', $validated) ? $request->integer('per_page') : 15;
        $courseId = array_key_exists('course_id', $validated) ? $request->integer('course_id') : null;

        $paginator = $this->batchCodeService->getStudentRedemptionsPaginated(
            $student,
            $perPage,
            $page,
            $courseId
        );

        $data = collect($paginator->items())->map(function (VideoCodeRedemption $redemption): array {
            $batch = $redemption->batch;
            $video = $batch->video;
            $course = $batch->course;

            return [
                'id' => $redemption->id,
                'code' => $this->codeGenerator->generateCode($batch, $redemption->sequence_number),
                'batch_code' => $batch->batch_code,
                'sequence_number' => $redemption->sequence_number,
                'video_id' => $batch->video_id,
                'video_title' => $video->translate('title'),
                'course_id' => $batch->course_id,
                'course_title' => $course->translate('title'),
                'center_id' => $batch->center_id,
                'view_limit' => $batch->view_limit_per_code,
                'redeemed_at' => $redemption->redeemed_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'page' => $paginator->currentPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
