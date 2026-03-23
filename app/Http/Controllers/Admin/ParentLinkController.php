<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Parents\AdminCreateLinkRequest;
use App\Http\Requests\Admin\Parents\AdminUpdateLinkRequest;
use App\Http\Resources\Admin\AdminParentStudentLinkResource;
use App\Models\Center;
use App\Models\User;
use App\Services\Parents\Contracts\ParentServiceInterface;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ParentLinkController extends Controller
{
    public function __construct(
        private readonly ParentServiceInterface $parentService
    ) {}

    public function store(AdminCreateLinkRequest $request, Center $center): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        /** @var array{parent_user_id: int, student_user_id: int} $data */
        $data = $request->validated();

        try {
            $link = $this->parentService->createLink(
                $admin,
                (int) $data['parent_user_id'],
                (int) $data['student_user_id'],
                (int) $center->id
            );

            return response()->json([
                'success' => true,
                'data' => new AdminParentStudentLinkResource($link),
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

    public function update(AdminUpdateLinkRequest $request, Center $center, int $link): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        /** @var array{action: 'approve'|'reject'|'revoke'} $data */
        $data = $request->validated();

        try {
            $updatedLink = match ($data['action']) {
                'approve' => $this->parentService->approveLink($admin, $link),
                'reject' => $this->parentService->rejectLink($admin, $link),
                'revoke' => $this->parentService->revokeLink($admin, $link),
            };

            return response()->json([
                'success' => true,
                'data' => new AdminParentStudentLinkResource($updatedLink),
            ]);
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
}
