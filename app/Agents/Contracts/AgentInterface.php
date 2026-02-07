<?php

declare(strict_types=1);

namespace App\Agents\Contracts;

use App\Enums\AgentType;
use App\Models\AgentExecution;
use App\Models\User;

/**
 * Base interface for all agents.
 * Agents are automated workflow executors that perform multi-step operations.
 */
interface AgentInterface
{
    /**
     * Get the agent type identifier.
     */
    public function getType(): AgentType;

    /**
     * Get a human-readable name for this agent.
     */
    public function getName(): string;

    /**
     * Get a description of what this agent does.
     */
    public function getDescription(): string;

    /**
     * Get the list of steps this agent will execute.
     *
     * @return array<int, string>
     */
    public function getSteps(): array;

    /**
     * Validate the execution context before running.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, string[]> Validation errors by field
     */
    public function validateContext(array $context): array;

    /**
     * Execute the agent workflow.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed> Result of the execution
     *
     * @throws \Exception If execution fails
     */
    public function execute(AgentExecution $execution, User $actor, array $context): array;

    /**
     * Check if the actor has permission to execute this agent.
     */
    public function canExecute(User $actor): bool;
}
