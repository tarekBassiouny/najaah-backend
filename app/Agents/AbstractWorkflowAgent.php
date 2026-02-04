<?php

declare(strict_types=1);

namespace App\Agents;

use App\Agents\Contracts\WorkflowAgentInterface;
use App\Models\AgentExecution;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Support\AuditActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Abstract base class for workflow agents.
 * Provides common functionality for executing multi-step workflows.
 */
abstract class AbstractWorkflowAgent implements WorkflowAgentInterface
{
    public function __construct(
        protected readonly CenterScopeService $centerScopeService,
        protected readonly AuditLogService $auditLogService
    ) {}

    /**
     * Execute the agent workflow with step-by-step tracking.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function execute(AgentExecution $execution, User $actor, array $context): array
    {
        $target = $this->resolveTarget($context);
        $steps = $this->getSteps();
        $stepResults = [];

        try {
            return DB::transaction(function () use ($execution, $actor, $context, $target, $steps, &$stepResults): array {
                $execution->markAsRunning();

                foreach ($steps as $step) {
                    $stepResult = $this->executeStep($execution, $step, $target, $context);
                    $stepResults[$step] = $stepResult;
                    $execution->addCompletedStep($step);
                }

                $result = [
                    'success' => true,
                    'steps' => $stepResults,
                    'target_id' => $target->getKey(),
                    'target_type' => get_class($target),
                ];

                $execution->markAsCompleted($result);

                $this->logExecution($execution, $actor, $target, AuditActions::AGENT_EXECUTED);

                return $result;
            });
        } catch (\Throwable $e) {
            // Attempt rollback
            $completedSteps = $execution->steps_completed ?? [];
            $this->rollback($execution, $target, $completedSteps, $context);

            $result = [
                'success' => false,
                'error' => $e->getMessage(),
                'steps_completed' => $completedSteps,
            ];

            $execution->markAsFailed($result);

            $this->logExecution($execution, $actor, $target, AuditActions::AGENT_FAILED, [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Resolve the target model from context.
     *
     * @param  array<string, mixed>  $context
     */
    abstract protected function resolveTarget(array $context): Model;

    /**
     * Default rollback implementation - override in subclasses for specific rollback logic.
     *
     * @param  array<int, string>  $completedSteps
     * @param  array<string, mixed>  $context
     */
    public function rollback(AgentExecution $execution, Model $target, array $completedSteps, array $context): void
    {
        // Default implementation does nothing.
        // Override in subclasses to implement specific rollback logic.
    }

    /**
     * Log agent execution to audit log.
     *
     * @param  array<string, mixed>  $metadata
     */
    protected function logExecution(
        AgentExecution $execution,
        User $actor,
        Model $target,
        string $action,
        array $metadata = []
    ): void {
        $this->auditLogService->log($actor, $target, $action, array_merge([
            'agent_type' => $this->getType()->value,
            'execution_id' => $execution->id,
        ], $metadata));
    }
}
