<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\Assignment\CreateGroupRequest;
use App\Http\Resources\Mobile\Assignment\AssignmentGroupResource;
use App\Models\Assignment;
use App\Models\AssignmentGroup;
use App\Models\User;
use App\Services\Assessments\Contracts\AssignmentSubmissionServiceInterface;
use Illuminate\Http\JsonResponse;

class AssignmentGroupController extends Controller
{
    public function __construct(
        private readonly AssignmentSubmissionServiceInterface $submissionService
    ) {}

    /**
     * List available groups for an assignment.
     */
    public function index(int $centerId, Assignment $assignment): JsonResponse
    {
        /** @var User|null $student */
        $student = request()->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access assignment groups.',
                ],
            ], 403);
        }

        if ($assignment->center_id !== $centerId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Assignment not found.',
                ],
            ], 404);
        }

        if (! $assignment->is_group_assignment) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_GROUP_ASSIGNMENT',
                    'message' => 'This is not a group assignment.',
                ],
            ], 400);
        }

        $groups = $assignment->groups()
            ->with(['members.user', 'creator'])
            ->withCount('members')
            ->get();

        // Check if student is in any group
        $myGroup = $groups->first(function ($group) use ($student) {
            return $group->members->contains('user_id', $student->id);
        });

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => [
                'groups' => AssignmentGroupResource::collection($groups),
                'my_group_id' => $myGroup?->id,
                'max_group_size' => $assignment->max_group_size,
            ],
        ]);
    }

    /**
     * Create a new group.
     */
    public function store(CreateGroupRequest $request, int $centerId, Assignment $assignment): JsonResponse
    {
        /** @var User $student */
        $student = $request->user();

        if ($assignment->center_id !== $centerId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Assignment not found.',
                ],
            ], 404);
        }

        if (! $assignment->is_group_assignment) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_GROUP_ASSIGNMENT',
                    'message' => 'This is not a group assignment.',
                ],
            ], 400);
        }

        // Check if student is already in a group
        $existingGroup = $assignment->groups()
            ->whereHas('members', fn ($q) => $q->where('user_id', $student->id))
            ->first();

        if ($existingGroup) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ALREADY_IN_GROUP',
                    'message' => 'You are already in a group for this assignment.',
                ],
            ], 400);
        }

        /** @var array{name: string} $data */
        $data = $request->validated();

        try {
            $group = $this->submissionService->createGroup($assignment, $student, $data['name']);
            $group->load(['members.user', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Group created successfully',
                'data' => new AssignmentGroupResource($group),
            ], 201);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CREATE_FAILED',
                    'message' => 'Failed to create group.',
                ],
            ], 500);
        }
    }

    /**
     * Show group details.
     */
    public function show(int $centerId, AssignmentGroup $group): JsonResponse
    {
        /** @var User|null $student */
        $student = request()->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access assignment groups.',
                ],
            ], 403);
        }

        $assignment = $group->assignment;
        if ($assignment->center_id !== $centerId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Group not found.',
                ],
            ], 404);
        }

        $group->load(['members.user', 'creator', 'submission']);

        return response()->json([
            'success' => true,
            'message' => 'Operation completed',
            'data' => new AssignmentGroupResource($group),
        ]);
    }

    /**
     * Join a group.
     */
    public function join(int $centerId, AssignmentGroup $group): JsonResponse
    {
        /** @var User|null $student */
        $student = request()->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can join groups.',
                ],
            ], 403);
        }

        $assignment = $group->assignment;
        if ($assignment->center_id !== $centerId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Group not found.',
                ],
            ], 404);
        }

        // Check if student is already in a group
        $existingGroup = $assignment->groups()
            ->whereHas('members', fn ($q) => $q->where('user_id', $student->id))
            ->first();

        if ($existingGroup) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ALREADY_IN_GROUP',
                    'message' => 'You are already in a group for this assignment.',
                ],
            ], 400);
        }

        // Check group size limit
        if ($assignment->max_group_size && $group->members()->count() >= $assignment->max_group_size) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'GROUP_FULL',
                    'message' => 'This group is full.',
                ],
            ], 400);
        }

        try {
            $this->submissionService->joinGroup($group, $student);
            $group->load(['members.user', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Joined group successfully',
                'data' => new AssignmentGroupResource($group),
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'JOIN_FAILED',
                    'message' => 'Failed to join group.',
                ],
            ], 500);
        }
    }

    /**
     * Leave a group.
     */
    public function leave(int $centerId, AssignmentGroup $group): JsonResponse
    {
        /** @var User|null $student */
        $student = request()->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can leave groups.',
                ],
            ], 403);
        }

        $assignment = $group->assignment;
        if ($assignment->center_id !== $centerId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Group not found.',
                ],
            ], 404);
        }

        // Check if student is in this group
        if (! $group->members()->where('user_id', $student->id)->exists()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_IN_GROUP',
                    'message' => 'You are not a member of this group.',
                ],
            ], 400);
        }

        try {
            $this->submissionService->leaveGroup($group, $student);

            return response()->json([
                'success' => true,
                'message' => 'Left group successfully',
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'LEAVE_FAILED',
                    'message' => 'Failed to leave group.',
                ],
            ], 500);
        }
    }
}
