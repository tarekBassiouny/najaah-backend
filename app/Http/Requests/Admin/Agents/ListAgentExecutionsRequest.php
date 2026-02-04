<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Agents;

use App\Filters\Admin\AgentExecutionFilters;
use App\Http\Requests\Admin\AdminListRequest;
use App\Support\Filters\FilterInput;

class ListAgentExecutionsRequest extends AdminListRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return array_merge($this->listRules(), [
            'center_id' => ['sometimes', 'integer'],
            'agent_type' => ['sometimes', 'string'],
            'status' => ['sometimes', 'integer'],
            'initiated_by' => ['sometimes', 'integer'],
        ]);
    }

    public function filters(): AgentExecutionFilters
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new AgentExecutionFilters(
            page: FilterInput::page($data),
            perPage: FilterInput::perPage($data),
            centerId: FilterInput::intOrNull($data, 'center_id'),
            agentType: FilterInput::stringOrNull($data, 'agent_type'),
            status: FilterInput::intOrNull($data, 'status'),
            initiatedBy: FilterInput::intOrNull($data, 'initiated_by')
        );
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function queryParameters(): array
    {
        return [
            'per_page' => [
                'description' => 'Items per page (max 100).',
                'example' => '15',
            ],
            'page' => [
                'description' => 'Page number to retrieve.',
                'example' => '1',
            ],
            'center_id' => [
                'description' => 'Filter by center ID.',
                'example' => '2',
            ],
            'agent_type' => [
                'description' => 'Filter by agent type (content_publishing, enrollment, etc.).',
                'example' => 'content_publishing',
            ],
            'status' => [
                'description' => 'Filter by status (0=pending, 1=running, 2=completed, 3=failed).',
                'example' => '2',
            ],
            'initiated_by' => [
                'description' => 'Filter by initiator user ID.',
                'example' => '1',
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [];
    }
}
