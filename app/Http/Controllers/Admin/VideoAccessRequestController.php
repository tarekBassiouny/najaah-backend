<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\WhatsAppCodeFormat;
use App\Filters\Admin\VideoAccessRequestFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VideoAccess\ApproveVideoAccessRequestRequest;
use App\Http\Requests\Admin\VideoAccess\BulkApproveVideoAccessRequestsRequest;
use App\Http\Requests\Admin\VideoAccess\BulkRejectVideoAccessRequestsRequest;
use App\Http\Requests\Admin\VideoAccess\ListVideoAccessRequestsRequest;
use App\Http\Requests\Admin\VideoAccess\RejectVideoAccessRequestRequest;
use App\Http\Resources\Admin\VideoAccess\VideoAccessCodeResource;
use App\Http\Resources\Admin\VideoAccess\VideoAccessRequestListResource;
use App\Http\Resources\Admin\VideoAccess\VideoAccessRequestResource;
use App\Models\Center;
use App\Models\User;
use App\Models\VideoAccessRequest;
use App\Services\Admin\VideoAccessRequestQueryService;
use App\Services\VideoAccess\Contracts\VideoApprovalRequestServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request as HttpRequest;

class VideoAccessRequestController extends Controller
{
    public function __construct(
        private readonly VideoApprovalRequestServiceInterface $service,
        private readonly VideoAccessRequestQueryService $queryService
    ) {}

    public function centerIndex(ListVideoAccessRequestsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request);

        $paginator = $this->queryService->paginateForCenter(
            $admin,
            (int) $center->id,
            $this->forCenter($request->filters())
        );

        return $this->listResponse($paginator);
    }

    public function centerApprove(
        ApproveVideoAccessRequestRequest $request,
        Center $center,
        VideoAccessRequest $videoAccessRequest
    ): JsonResponse {
        $admin = $this->requireAdmin($request);
        $this->assertRequestBelongsToCenter($center, $videoAccessRequest);

        $result = $this->service->approve(
            admin: $admin,
            request: $videoAccessRequest,
            decisionReason: $request->input('decision_reason'),
            sendWhatsApp: (bool) $request->boolean('send_whatsapp', false),
            format: $this->parseFormat($request->input('whatsapp_format'))
        );

        $generatedCodeResource = new VideoAccessCodeResource($result->generatedCode->loadMissing(['user', 'video', 'course', 'request', 'generator', 'revoker']));

        return response()->json([
            'success' => true,
            'message' => 'Request approved successfully',
            'data' => [
                'request' => new VideoAccessRequestResource($result->request),
                'generated_code' => array_merge($generatedCodeResource->resolve(), [
                    'whatsapp_sent' => $result->whatsAppSent,
                    'whatsapp_error' => $result->whatsAppError,
                ]),
            ],
        ]);
    }

    public function centerReject(
        RejectVideoAccessRequestRequest $request,
        Center $center,
        VideoAccessRequest $videoAccessRequest
    ): JsonResponse {
        $admin = $this->requireAdmin($request);
        $this->assertRequestBelongsToCenter($center, $videoAccessRequest);

        $rejected = $this->service->reject(
            $admin,
            $videoAccessRequest,
            $request->input('decision_reason')
        );

        return response()->json([
            'success' => true,
            'message' => 'Request rejected successfully',
            'data' => new VideoAccessRequestResource($rejected),
        ]);
    }

    public function centerBulkApprove(BulkApproveVideoAccessRequestsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request);

        /** @var array{request_ids:array<int,int>,decision_reason:?string,send_whatsapp?:bool,whatsapp_format?:string} $data */
        $data = $request->validated();

        $result = $this->service->bulkApprove(
            admin: $admin,
            requestIds: $data['request_ids'],
            decisionReason: $data['decision_reason'] ?? null,
            sendWhatsApp: (bool) ($data['send_whatsapp'] ?? false),
            format: $this->parseFormat($data['whatsapp_format'] ?? null),
            forcedCenterId: (int) $center->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Bulk approval processed.',
            'data' => $result,
        ]);
    }

    public function centerBulkReject(BulkRejectVideoAccessRequestsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin($request);

        /** @var array{request_ids:array<int,int>,decision_reason:?string} $data */
        $data = $request->validated();

        $result = $this->service->bulkReject(
            admin: $admin,
            requestIds: $data['request_ids'],
            decisionReason: $data['decision_reason'] ?? null,
            forcedCenterId: (int) $center->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Bulk rejection processed.',
            'data' => $result,
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

    private function assertRequestBelongsToCenter(Center $center, VideoAccessRequest $request): void
    {
        if ((int) $request->center_id !== (int) $center->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Video access request not found.',
                ],
            ], 404));
        }
    }

    private function parseFormat(?string $format): ?WhatsAppCodeFormat
    {
        if ($format === null || $format === '') {
            return null;
        }

        return WhatsAppCodeFormat::from($format);
    }

    private function forCenter(VideoAccessRequestFilters $filters): VideoAccessRequestFilters
    {
        return new VideoAccessRequestFilters(
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
     * @param  LengthAwarePaginator<VideoAccessRequest>  $paginator
     */
    private function listResponse(LengthAwarePaginator $paginator): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Video access requests retrieved successfully',
            'data' => VideoAccessRequestListResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
