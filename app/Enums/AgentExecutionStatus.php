<?php

declare(strict_types=1);

namespace App\Enums;

enum AgentExecutionStatus: int
{
    case Pending = 0;
    case Running = 1;
    case Completed = 2;
    case Failed = 3;
}
