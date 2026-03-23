<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Parent;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\RequestLinkRequest;
use App\Http\Resources\Parent\ParentStudentResource;
use App\Models\ParentStudentLink;
use App\Models\User;
use App\Services\Parents\Contracts\ParentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LinkController extends Controller
{
    public function __construct(
        private readonly ParentServiceInterface $parentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $parent */
        $parent = $request->user();
        $centerId = $this->resolveCenterId($request);

        $links = ParentStudentLink::query()
            ->with(['student:id,name,phone'])
            ->forParent($parent->id)
            ->when(is_numeric($centerId), fn ($q) => $q->forCenter($centerId))
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ParentStudentResource::collection($links),
        ]);
    }

    public function store(RequestLinkRequest $request): JsonResponse
    {
        /** @var User $parent */
        $parent = $request->user();
        $centerId = $this->resolveCenterId($request);

        /** @var array{student_phone: string} $data */
        $data = $request->validated();

        try {
            $link = $this->parentService->requestLink($parent, $data['student_phone'], $centerId);

            return response()->json([
                'success' => true,
                'data' => new ParentStudentResource($link),
            ], Response::HTTP_CREATED);
        } catch (DomainException $domainException) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $domainException->errorCode(),
                    'message' => $domainException->getMessage(),
                ],
            ], $domainException->statusCode());
        }
    }

    private function resolveCenterId(Request $request): ?int
    {
        $resolvedCenterId = $request->attributes->get('resolved_center_id');

        return is_numeric($resolvedCenterId) ? (int) $resolvedCenterId : null;
    }
}
