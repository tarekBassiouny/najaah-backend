<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Agents\Contracts\AgentInterface;
use App\Enums\AgentType;
use App\Models\AgentExecution;
use App\Models\User;

final class FakeAgentForExecutionTest implements AgentInterface
{
    public bool $canRun = true;

    /** @var array<string, string[]> */
    public array $errors = [];

    public function getType(): AgentType
    {
        return AgentType::ContentPublishing;
    }

    public function getName(): string
    {
        return 'Fake Agent';
    }

    public function getDescription(): string
    {
        return 'Fake';
    }

    public function getSteps(): array
    {
        return ['one', 'two'];
    }

    public function validateContext(array $context): array
    {
        return $this->errors;
    }

    public function execute(AgentExecution $execution, User $actor, array $context): array
    {
        return ['ok' => true, 'execution_id' => $execution->id];
    }

    public function canExecute(User $actor): bool
    {
        return $this->canRun;
    }
}
