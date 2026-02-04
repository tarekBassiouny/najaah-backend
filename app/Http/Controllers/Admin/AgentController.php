<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AgentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Agents\ExecuteAgentRequest;
use App\Http\Requests\Admin\Agents\ListAgentExecutionsRequest;
use App\Http\Resources\Admin\AgentExecutionResource;
use App\Models\AgentExecution;
use App\Models\User;
use App\Services\Agents\AgentExecutionService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class AgentController extends Controller
{
    public function __construct(
        private readonly AgentExecutionService $executionService
    ) {}

    /**
     * List agent executions.
     */
    public function index(ListAgentExecutionsRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();
        $filters = $request->filters();
        $executions = $this->executionService->paginateForAdmin($admin, $filters);

        return response()->json([
            'success' => true,
            'data' => AgentExecutionResource::collection($executions),
            'meta' => [
                'page' => $executions->currentPage(),
                'per_page' => $executions->perPage(),
                'total' => $executions->total(),
                'last_page' => $executions->lastPage(),
            ],
        ]);
    }

    /**
     * Show a single agent execution.
     */
    public function show(AgentExecution $agentExecution): JsonResponse
    {
        $admin = $this->requireAdmin();

        $this->executionService->assertAdminCanAccess($admin, $agentExecution);

        return response()->json([
            'success' => true,
            'data' => new AgentExecutionResource(
                $agentExecution->load(['center', 'initiator', 'target'])
            ),
        ]);
    }

    /**
     * Get available agents for the current user.
     */
    public function available(): JsonResponse
    {
        $admin = $this->requireAdmin();
        $agents = $this->executionService->getAvailableAgents($admin);

        return response()->json([
            'success' => true,
            'data' => $agents,
        ]);
    }

    /**
     * Execute an agent.
     */
    public function execute(ExecuteAgentRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();

        /** @var array{agent_type:string,center_id:int,context:array<string,mixed>} $data */
        $data = $request->validated();

        $agentType = AgentType::from($data['agent_type']);
        $centerId = (int) $data['center_id'];
        /** @var array<string, mixed> $context */
        $context = $data['context'] ?? [];

        $result = $this->executionService->execute($agentType, $admin, $centerId, $context);

        return response()->json([
            'success' => true,
            'message' => 'Agent execution completed',
            'data' => $result,
        ], 201);
    }

    /**
     * Execute content publishing agent.
     */
    public function executeContentPublishing(ExecuteAgentRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();

        /** @var array{center_id:int,context:array<string,mixed>} $data */
        $data = $request->validated();

        $centerId = (int) $data['center_id'];
        /** @var array<string, mixed> $context */
        $context = $data['context'] ?? [];

        $result = $this->executionService->execute(
            AgentType::ContentPublishing,
            $admin,
            $centerId,
            $context
        );

        return response()->json([
            'success' => true,
            'message' => 'Content publishing completed',
            'data' => $result,
        ], 201);
    }

    /**
     * Execute bulk enrollment agent.
     */
    public function executeBulkEnrollment(ExecuteAgentRequest $request): JsonResponse
    {
        $admin = $this->requireAdmin();

        /** @var array{center_id:int,context:array<string,mixed>} $data */
        $data = $request->validated();

        $centerId = (int) $data['center_id'];
        /** @var array<string, mixed> $context */
        $context = $data['context'] ?? [];

        $result = $this->executionService->execute(
            AgentType::Enrollment,
            $admin,
            $centerId,
            $context
        );

        return response()->json([
            'success' => true,
            'message' => 'Bulk enrollment completed',
            'data' => $result,
        ], 201);
    }

    private function requireAdmin(): User
    {
        $admin = request()->user();

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
}
