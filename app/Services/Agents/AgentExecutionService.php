<?php

declare(strict_types=1);

namespace App\Services\Agents;

use App\Agents\Contracts\AgentInterface;
use App\Enums\AgentExecutionStatus;
use App\Enums\AgentType;
use App\Filters\Admin\AgentExecutionFilters;
use App\Models\AgentExecution;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;

final readonly class AgentExecutionService
{
    /** @var array<string, class-string<AgentInterface>> */
    private array $agents;

    public function __construct(
        private CenterScopeService $centerScopeService
    ) {
        $this->agents = config('agents.registry', []);
    }

    /**
     * Create a new agent execution record.
     *
     * @param  array<string, mixed>  $context
     */
    public function createExecution(
        AgentType $agentType,
        User $actor,
        int $centerId,
        array $context = [],
        ?Model $target = null
    ): AgentExecution {
        $execution = new AgentExecution([
            'center_id' => $centerId,
            'agent_type' => $agentType,
            'status' => AgentExecutionStatus::Pending,
            'context' => $context,
            'initiated_by' => $actor->id,
            'steps_completed' => [],
        ]);

        if ($target !== null) {
            $execution->target_type = get_class($target);
            $execution->target_id = $target->getKey();
        }

        $execution->save();

        return $execution;
    }

    /**
     * Execute an agent workflow.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function execute(AgentType $agentType, User $actor, int $centerId, array $context = []): array
    {
        $agent = $this->resolveAgent($agentType);

        // Ensure admin has access to target center
        $this->centerScopeService->assertAdminCenterId($actor, $centerId);

        // Validate actor can execute
        if (! $agent->canExecute($actor)) {
            throw ValidationException::withMessages([
                'agent' => ['You are not authorized to execute this agent.'],
            ]);
        }

        // Validate context
        $errors = $agent->validateContext($context);
        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        // Create execution record
        $execution = $this->createExecution($agentType, $actor, $centerId, $context);

        // Execute the agent
        return $agent->execute($execution, $actor, $context);
    }

    /**
     * Get an agent execution by ID.
     */
    public function getExecution(int $id): ?AgentExecution
    {
        return AgentExecution::find($id);
    }

    /**
     * Get paginated list of executions for admin.
     *
     * @return LengthAwarePaginator<AgentExecution>
     */
    public function paginateForAdmin(User $admin, AgentExecutionFilters $filters): LengthAwarePaginator
    {
        $query = AgentExecution::query()
            ->with(['center', 'initiator', 'target'])
            ->orderByDesc('created_at');

        // Scope to admin's accessible centers
        $centerIds = $this->centerScopeService->getAccessibleCenterIds($admin);
        if ($centerIds !== null) {
            $query->whereIn('center_id', $centerIds);
        }

        // Apply filters
        if ($filters->centerId !== null) {
            $query->where('center_id', $filters->centerId);
        }

        if ($filters->agentType !== null) {
            $query->where('agent_type', $filters->agentType);
        }

        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }

        if ($filters->initiatedBy !== null) {
            $query->where('initiated_by', $filters->initiatedBy);
        }

        return $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * Assert admin can access an execution.
     */
    public function assertAdminCanAccess(User $admin, AgentExecution $execution): void
    {
        $this->centerScopeService->assertAdminSameCenter($admin, $execution);
    }

    /**
     * Get available agents for a user.
     *
     * @return array<string, array{type: string, name: string, description: string, steps: array<int, string>}>
     */
    public function getAvailableAgents(User $actor): array
    {
        $available = [];

        foreach ($this->agents as $type => $agentClass) {
            $agent = $this->resolveAgent(AgentType::from($type));

            if ($agent->canExecute($actor)) {
                $available[$type] = [
                    'type' => $type,
                    'name' => $agent->getName(),
                    'description' => $agent->getDescription(),
                    'steps' => $agent->getSteps(),
                ];
            }
        }

        return $available;
    }

    /**
     * Resolve an agent instance by type.
     */
    private function resolveAgent(AgentType $agentType): AgentInterface
    {
        if (! isset($this->agents[$agentType->value])) {
            throw ValidationException::withMessages([
                'agent_type' => [sprintf("Agent type '%s' is not registered.", $agentType->value)],
            ]);
        }

        $agentClass = $this->agents[$agentType->value];

        return App::make($agentClass);
    }
}
