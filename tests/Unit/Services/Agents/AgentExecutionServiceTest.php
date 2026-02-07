<?php

declare(strict_types=1);

use App\Enums\AgentType;
use App\Filters\Admin\AgentExecutionFilters;
use App\Models\AgentExecution;
use App\Models\Center;
use App\Services\Agents\AgentExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Helpers\AdminTestHelper;
use Tests\Helpers\FakeAgentForExecutionTest;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class, AdminTestHelper::class)->group('agents', 'services');

it('creates execution and executes registered agent', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();

    config()->set('agents.registry', [
        AgentType::ContentPublishing->value => FakeAgentForExecutionTest::class,
    ]);
    app()->bind(FakeAgentForExecutionTest::class, fn () => new FakeAgentForExecutionTest);

    $service = app(AgentExecutionService::class);
    $result = $service->execute(AgentType::ContentPublishing, $admin, $center->id, ['foo' => 'bar']);

    expect($result['ok'])->toBeTrue();
    expect(AgentExecution::query()->count())->toBe(1);
});

it('paginates executions for admin with filters', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();
    AgentExecution::query()->create([
        'center_id' => $center->id,
        'agent_type' => AgentType::ContentPublishing,
        'status' => 0,
        'context' => ['a' => 1],
        'initiated_by' => $admin->id,
        'steps_completed' => [],
    ]);

    $service = app(AgentExecutionService::class);
    $filters = new AgentExecutionFilters(1, 15, $center->id, AgentType::ContentPublishing->value, 0, $admin->id);
    $page = $service->paginateForAdmin($admin, $filters);

    expect($page->total())->toBe(1);
});

it('fails execute when context validation returns errors', function (): void {
    $admin = $this->asAdmin();
    $center = Center::factory()->create();

    config()->set('agents.registry', [
        AgentType::ContentPublishing->value => FakeAgentForExecutionTest::class,
    ]);
    app()->bind(FakeAgentForExecutionTest::class, function () {
        $agent = new FakeAgentForExecutionTest;
        $agent->errors = ['context' => ['invalid']];

        return $agent;
    });

    $service = app(AgentExecutionService::class);
    $service->execute(AgentType::ContentPublishing, $admin, $center->id, ['x' => 'y']);
})->throws(ValidationException::class);
