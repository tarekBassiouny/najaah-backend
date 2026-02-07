<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Agent Registry
    |--------------------------------------------------------------------------
    |
    | This is the registry of available agents. Each key is the agent type
    | (as defined in AgentType enum) and the value is the fully qualified
    | class name of the agent implementation.
    |
    */
    'registry' => [
        'content_publishing' => \App\Agents\ContentPublishingAgent::class,
        'enrollment' => \App\Agents\EnrollmentManagementAgent::class,
        // 'analytics' => \App\Agents\AnalyticsAgent::class,
        // 'notification' => \App\Agents\NotificationAgent::class,
    ],
];
