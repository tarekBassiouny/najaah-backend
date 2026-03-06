<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\WhatsAppCodeFormat;
use App\Filters\Admin\VideoAccessCodeFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VideoAccess\BulkGenerateVideoAccessCodesRequest;
use App\Http\Requests\Admin\VideoAccess\BulkRevokeVideoAccessCodesRequest;
use App\Http\Requests\Admin\VideoAccess\BulkSendVideoAccessCodesWhatsAppRequest;
use App\Http\Requests\Admin\VideoAccess\GenerateVideoAccessCodeRequest;
use App\Http\Requests\Admin\VideoAccess\ListVideoAccessCodesRequest;
use App\Http\Requests\Admin\VideoAccess\SendVideoAccessCodeWhatsAppRequest;
use App\Http\Resources\Admin\VideoAccess\VideoAccessCodeListResource;
use App\Http\Resources\Admin\VideoAccess\VideoAccessCodeResource;
use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAccessCode;
use App\Services\Access\CourseAccessService;
use App\Services\Access\EnrollmentAccessService;
use App\Services\Admin\VideoAccessCodeQueryService;
use App\Services\VideoAccess\Contracts\BulkWhatsAppServiceInterface;
use App\Services\VideoAccess\Contracts\VideoApprovalCodeServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;

class VideoAccessCodeController extends Controller
{
    public function __construct(
        private readonly VideoApprovalCodeServiceInterface $codeService,
        private readonly VideoAccessCodeQueryService $queryService,
        private readonly BulkWhatsAppServiceInterface $bulkWhatsAppService,
        private readonly EnrollmentAccessService $enrollmentAccessService,
        private readonly CourseAccessService $courseAccessService
    ) {}

    public function centerIndex(ListVideoAccessCodesRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request);

        $paginator = $this->queryService->paginateForCenter(
            $admin,
            (int) $center->id,
            $this->forCenter($request->filters())
        );

        return $this->listResponse($paginator);
    }

    public function systemGenerateForStudent(
        GenerateVideoAccessCodeRequest $request,
        User $student
    ): JsonResponse {
        $admin = $this->requireAdmin($request);

        /** @var array{video_id:int,course_id:int,send_whatsapp?:bool,whatsapp_format?:string} $data */
        $data = $request->validated();

        /** @var Course $course */
        $course = Course::query()->findOrFail((int) $data['course_id']);
        $this->assertStudentBelongsToCenter($student, (int) $course->center_id);

        $video = $this->resolveVideo((int) $data['video_id']);
        $this->courseAccessService->assertVideoInCourse($course, $video);
        $enrollment = $this->resolveEnrollment($student, $course);

        $code = $this->codeService->generate($admin, $student, $video, $course, $enrollment);

        $whatsAppSent = false;
        $whatsAppError = null;

        if ((bool) ($data['send_whatsapp'] ?? false)) {
            try {
                $this->codeService->sendViaWhatsApp(
                    $code,
                    $this->parseFormat($data['whatsapp_format'] ?? null) ?? WhatsAppCodeFormat::TextCode
                );
                $whatsAppSent = true;
            } catch (\Throwable $throwable) {
                $whatsAppError = $throwable->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Code generated successfully',
            'data' => array_merge(
                (new VideoAccessCodeResource($code->loadMissing(['user', 'video', 'course', 'request', 'generator', 'revoker'])))->resolve(),
                [
                    'whatsapp_sent' => $whatsAppSent,
                    'whatsapp_error' => $whatsAppError,
                ]
            ),
        ], 201);
    }

    public function centerGenerateForStudent(
        GenerateVideoAccessCodeRequest $request,
        Center $center,
        User $student
    ): JsonResponse {
        $admin = $this->requireAdmin($request);
        $this->assertStudentBelongsToCenter($student, (int) $center->id);

        /** @var array{video_id:int,course_id:int,send_whatsapp?:bool,whatsapp_format?:string} $data */
        $data = $request->validated();

        $course = $this->resolveCourse((int) $data['course_id'], $center);
        $video = $this->resolveVideo((int) $data['video_id']);
        $this->courseAccessService->assertVideoInCourse($course, $video);
        $enrollment = $this->resolveEnrollment($student, $course);

        $code = $this->codeService->generate($admin, $student, $video, $course, $enrollment);

        $whatsAppSent = false;
        $whatsAppError = null;

        if ((bool) ($data['send_whatsapp'] ?? false)) {
            try {
                $this->codeService->sendViaWhatsApp(
                    $code,
                    $this->parseFormat($data['whatsapp_format'] ?? null) ?? WhatsAppCodeFormat::TextCode
                );
                $whatsAppSent = true;
            } catch (\Throwable $throwable) {
                $whatsAppError = $throwable->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Code generated successfully',
            'data' => array_merge(
                (new VideoAccessCodeResource($code->loadMissing(['user', 'video', 'course', 'request', 'generator', 'revoker'])))->resolve(),
                [
                    'whatsapp_sent' => $whatsAppSent,
                    'whatsapp_error' => $whatsAppError,
                ]
            ),
        ], 201);
    }

    public function systemBulkGenerate(BulkGenerateVideoAccessCodesRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin($request);

        /** @var array{student_ids:array<int,int>,video_id:int,course_id:int,send_whatsapp?:bool,whatsapp_format?:string} $data */
        $data = $request->validated();

        /** @var Course $course */
        $course = Course::query()->findOrFail((int) $data['course_id']);
        $video = $this->resolveVideo((int) $data['video_id']);
        $this->courseAccessService->assertVideoInCourse($course, $video);

        $uniqueStudentIds = array_values(array_unique(array_map('intval', $data['student_ids'])));

        $students = User::query()
            ->whereIn('id', $uniqueStudentIds)
            ->where('is_student', true)
            ->get()
            ->keyBy('id');

        $generated = [];
        $failed = [];
        $whatsAppSent = 0;
        $whatsAppFailed = 0;

        $sendWhatsApp = (bool) ($data['send_whatsapp'] ?? false);
        $format = $this->parseFormat($data['whatsapp_format'] ?? null) ?? WhatsAppCodeFormat::TextCode;

        foreach ($uniqueStudentIds as $studentId) {
            /** @var User|null $student */
            $student = $students->get($studentId);

            if (! $student instanceof User || ! $student->belongsToCenter((int) $course->center_id)) {
                $failed[] = [
                    'student_id' => $studentId,
                    'reason' => 'Student not found.',
                ];

                continue;
            }

            try {
                $enrollment = $this->resolveEnrollment($student, $course);
                $code = $this->codeService->generate($admin, $student, $video, $course, $enrollment);

                $codeData = (new VideoAccessCodeResource($code->loadMissing(['user', 'video', 'course', 'request', 'generator', 'revoker'])))->resolve();

                if ($sendWhatsApp) {
                    try {
                        $this->codeService->sendViaWhatsApp($code, $format);
                        $whatsAppSent++;
                        $codeData['whatsapp_sent'] = true;
                    } catch (\Throwable $throwable) {
                        $whatsAppFailed++;
                        $codeData['whatsapp_sent'] = false;
                        $codeData['whatsapp_error'] = $throwable->getMessage();
                    }
                }

                $generated[] = $codeData;
            } catch (\Throwable $throwable) {
                $failed[] = [
                    'student_id' => $studentId,
                    'reason' => $throwable->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk code generation processed.',
            'data' => [
                'counts' => [
                    'total' => count($uniqueStudentIds),
                    'generated' => count($generated),
                    'failed' => count($failed),
                    'whatsapp_sent' => $whatsAppSent,
                    'whatsapp_failed' => $whatsAppFailed,
                ],
                'generated' => $generated,
                'failed' => $failed,
            ],
        ]);
    }

    public function centerBulkGenerate(BulkGenerateVideoAccessCodesRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request);

        /** @var array{student_ids:array<int,int>,video_id:int,course_id:int,send_whatsapp?:bool,whatsapp_format?:string} $data */
        $data = $request->validated();

        $course = $this->resolveCourse((int) $data['course_id'], $center);
        $video = $this->resolveVideo((int) $data['video_id']);
        $this->courseAccessService->assertVideoInCourse($course, $video);

        $uniqueStudentIds = array_values(array_unique(array_map('intval', $data['student_ids'])));

        $students = User::query()
            ->whereIn('id', $uniqueStudentIds)
            ->where('is_student', true)
            ->get()
            ->keyBy('id');

        $generated = [];
        $failed = [];
        $whatsAppSent = 0;
        $whatsAppFailed = 0;

        $sendWhatsApp = (bool) ($data['send_whatsapp'] ?? false);
        $format = $this->parseFormat($data['whatsapp_format'] ?? null) ?? WhatsAppCodeFormat::TextCode;

        foreach ($uniqueStudentIds as $studentId) {
            /** @var User|null $student */
            $student = $students->get($studentId);

            if (! $student instanceof User || ! $student->belongsToCenter((int) $center->id)) {
                $failed[] = [
                    'student_id' => $studentId,
                    'reason' => 'Student not found.',
                ];

                continue;
            }

            try {
                $enrollment = $this->resolveEnrollment($student, $course);
                $code = $this->codeService->generate($admin, $student, $video, $course, $enrollment);

                $codeData = (new VideoAccessCodeResource($code->loadMissing(['user', 'video', 'course', 'request', 'generator', 'revoker'])))->resolve();

                if ($sendWhatsApp) {
                    try {
                        $this->codeService->sendViaWhatsApp($code, $format);
                        $whatsAppSent++;
                        $codeData['whatsapp_sent'] = true;
                    } catch (\Throwable $throwable) {
                        $whatsAppFailed++;
                        $codeData['whatsapp_sent'] = false;
                        $codeData['whatsapp_error'] = $throwable->getMessage();
                    }
                }

                $generated[] = $codeData;
            } catch (\Throwable $throwable) {
                $failed[] = [
                    'student_id' => $studentId,
                    'reason' => $throwable->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk code generation processed.',
            'data' => [
                'counts' => [
                    'total' => count($uniqueStudentIds),
                    'generated' => count($generated),
                    'failed' => count($failed),
                    'whatsapp_sent' => $whatsAppSent,
                    'whatsapp_failed' => $whatsAppFailed,
                ],
                'generated' => $generated,
                'failed' => $failed,
            ],
        ]);
    }

    public function centerShow(HttpRequest $request, Center $center, VideoAccessCode $code): JsonResponse
    {
        $this->requireAdmin($request);
        $this->assertCodeBelongsToCenter($center, $code);

        return response()->json([
            'success' => true,
            'data' => new VideoAccessCodeResource($code->loadMissing(['user', 'video', 'course', 'request', 'generator', 'revoker'])),
        ]);
    }

    public function centerRegenerate(HttpRequest $request, Center $center, VideoAccessCode $code): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertCodeBelongsToCenter($center, $code);

        $regenerated = $this->codeService->regenerate($admin, $code);

        return response()->json([
            'success' => true,
            'message' => 'Code regenerated successfully.',
            'data' => new VideoAccessCodeResource($regenerated->loadMissing(['user', 'video', 'course', 'request', 'generator', 'revoker'])),
        ]);
    }

    public function centerRevoke(HttpRequest $request, Center $center, VideoAccessCode $code): JsonResponse
    {
        $admin = $this->requireAdmin($request);
        $this->assertCodeBelongsToCenter($center, $code);

        $revoked = $this->codeService->revoke($admin, $code);

        return response()->json([
            'success' => true,
            'message' => 'Code revoked successfully.',
            'data' => new VideoAccessCodeResource($revoked->loadMissing(['user', 'video', 'course', 'request', 'generator', 'revoker'])),
        ]);
    }

    public function centerBulkRevoke(BulkRevokeVideoAccessCodesRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request);

        /** @var array{code_ids:array<int,int>} $data */
        $data = $request->validated();

        $uniqueIds = array_values(array_unique(array_map('intval', $data['code_ids'])));

        $codes = VideoAccessCode::query()
            ->whereIn('id', $uniqueIds)
            ->where('center_id', $center->id)
            ->get()
            ->keyBy('id');

        $revoked = [];
        $failed = [];

        foreach ($uniqueIds as $codeId) {
            /** @var VideoAccessCode|null $code */
            $code = $codes->get($codeId);

            if (! $code instanceof VideoAccessCode) {
                $failed[] = [
                    'code_id' => $codeId,
                    'reason' => 'Code not found.',
                ];

                continue;
            }

            try {
                $revoked[] = $this->codeService->revoke($admin, $code)
                    ->loadMissing(['user', 'video', 'course', 'request', 'generator', 'revoker']);
            } catch (\Throwable $throwable) {
                $failed[] = [
                    'code_id' => $codeId,
                    'reason' => $throwable->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk revoke processed.',
            'data' => [
                'counts' => [
                    'total' => count($uniqueIds),
                    'revoked' => count($revoked),
                    'failed' => count($failed),
                ],
                'revoked' => VideoAccessCodeResource::collection($revoked),
                'failed' => $failed,
            ],
        ]);
    }

    public function centerSendWhatsApp(
        SendVideoAccessCodeWhatsAppRequest $request,
        Center $center,
        VideoAccessCode $code
    ): JsonResponse {
        $this->requireAdmin($request);
        $this->assertCodeBelongsToCenter($center, $code);

        $this->codeService->sendViaWhatsApp($code->loadMissing(['user', 'video']), WhatsAppCodeFormat::from((string) $request->input('format')));

        return response()->json([
            'success' => true,
            'message' => 'Code sent to student via WhatsApp',
        ]);
    }

    public function centerBulkSendWhatsApp(BulkSendVideoAccessCodesWhatsAppRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request);

        /** @var array{code_ids:array<int,int>,format:string} $data */
        $data = $request->validated();

        $job = $this->bulkWhatsAppService->initiate(
            admin: $admin,
            centerId: (int) $center->id,
            codeIds: $data['code_ids'],
            format: WhatsAppCodeFormat::from($data['format'])
        );

        return response()->json([
            'success' => true,
            'message' => 'Bulk WhatsApp send job started.',
            'data' => [
                'job_id' => $job->id,
                'status' => 'processing',
                'total_codes' => $job->total_codes,
                'estimated_minutes' => $this->estimateMinutes($job),
            ],
        ]);
    }

    private function requireAdmin(HttpRequest $request): User
    {
        /** @var User|null $admin */
        $admin = $request->user();

        if (! $admin instanceof User) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401));
        }

        return $admin;
    }

    private function assertStudentBelongsToCenter(User $student, int $centerId): void
    {
        if (! $student->is_student || ! $student->belongsToCenter($centerId)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Student not found.',
                ],
            ], 404));
        }
    }

    private function assertCodeBelongsToCenter(Center $center, VideoAccessCode $code): void
    {
        if ((int) $code->center_id !== (int) $center->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Video access code not found.',
                ],
            ], 404));
        }
    }

    private function resolveCourse(int $courseId, Center $center): Course
    {
        /** @var Course|null $course */
        $course = Course::query()->find($courseId);

        if (! $course instanceof Course || (int) $course->center_id !== (int) $center->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Course not found.',
                ],
            ], 404));
        }

        return $course;
    }

    private function resolveVideo(int $videoId): Video
    {
        /** @var Video|null $video */
        $video = Video::query()->find($videoId);

        if (! $video instanceof Video) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Video not found.',
                ],
            ], 404));
        }

        return $video;
    }

    private function resolveEnrollment(User $student, Course $course): Enrollment
    {
        return $this->enrollmentAccessService->assertActiveEnrollment($student, $course);
    }

    private function parseFormat(?string $format): ?WhatsAppCodeFormat
    {
        if ($format === null || $format === '') {
            return null;
        }

        return WhatsAppCodeFormat::from($format);
    }

    private function forCenter(VideoAccessCodeFilters $filters): VideoAccessCodeFilters
    {
        return new VideoAccessCodeFilters(
            page: $filters->page,
            perPage: $filters->perPage,
            status: $filters->status,
            userId: $filters->userId,
            videoId: $filters->videoId,
            courseId: $filters->courseId,
            search: $filters->search,
            dateFrom: $filters->dateFrom,
            dateTo: $filters->dateTo,
        );
    }

    /**
     * @param  LengthAwarePaginator<VideoAccessCode>  $paginator
     */
    private function listResponse(LengthAwarePaginator $paginator): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Video access codes retrieved successfully',
            'data' => VideoAccessCodeListResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    private function estimateMinutes(\App\Models\BulkWhatsAppJob $job): int
    {
        $settings = is_array($job->settings) ? $job->settings : [];
        $delay = max(0, (int) ($settings['delay_seconds'] ?? 3));
        $batchSize = max(1, (int) ($settings['batch_size'] ?? 50));
        $batchPause = max(0, (int) ($settings['batch_pause_seconds'] ?? 60));

        $codes = max(0, (int) $job->total_codes);
        if ($codes === 0) {
            return 0;
        }

        $batches = (int) ceil($codes / $batchSize);
        $seconds = ($codes * $delay) + max(0, $batches - 1) * $batchPause;

        return (int) ceil($seconds / 60);
    }
}
