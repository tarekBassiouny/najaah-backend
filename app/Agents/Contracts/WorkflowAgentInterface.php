<?php

declare(strict_types=1);

namespace App\Agents\Contracts;

use App\Models\AgentExecution;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface for workflow agents that operate on specific targets.
 * Extends the base AgentInterface with target-specific functionality.
 */
interface WorkflowAgentInterface extends AgentInterface
{
    /**
     * Get the class name of the target model this agent operates on.
     *
     * @return class-string<Model>
     */
    public function getTargetClass(): string;

    /**
     * Validate that the target exists and is in a valid state for this workflow.
     *
     * @return array<string, string[]> Validation errors by field
     */
    public function validateTarget(Model $target): array;

    /**
     * Execute a single step of the workflow.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed> Step result
     *
     * @throws \Exception If step fails
     */
    public function executeStep(AgentExecution $execution, string $step, Model $target, array $context): array;

    /**
     * Handle rollback if execution fails mid-workflow.
     *
     * @param  array<int, string>  $completedSteps
     * @param  array<string, mixed>  $context
     */
    public function rollback(AgentExecution $execution, Model $target, array $completedSteps, array $context): void;
}
