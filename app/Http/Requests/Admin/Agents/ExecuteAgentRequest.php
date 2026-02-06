<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Agents;

use App\Enums\AgentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExecuteAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'agent_type' => ['sometimes', 'string', Rule::enum(AgentType::class)],
            'center_id' => ['required', 'integer', 'exists:centers,id'],
            'context' => ['sometimes', 'array'],
            'context.course_id' => ['sometimes', 'integer', 'exists:courses,id'],
            'context.student_ids' => ['sometimes', 'array'],
            'context.student_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'agent_type' => [
                'description' => 'The type of agent to execute (content_publishing, enrollment, etc.).',
                'example' => 'content_publishing',
            ],
            'center_id' => [
                'description' => 'The center ID to execute the agent for.',
                'example' => 1,
            ],
            'context' => [
                'description' => 'Agent-specific context parameters.',
                'example' => ['course_id' => 1],
            ],
        ];
    }
}
