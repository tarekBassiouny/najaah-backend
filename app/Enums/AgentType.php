<?php

declare(strict_types=1);

namespace App\Enums;

enum AgentType: string
{
    case ContentPublishing = 'content_publishing';
    case Enrollment = 'enrollment';
    case Analytics = 'analytics';
    case Notification = 'notification';
}
