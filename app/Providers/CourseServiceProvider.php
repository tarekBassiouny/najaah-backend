<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Courses\Contracts\CourseAttachmentServiceInterface;
use App\Services\Courses\Contracts\CourseServiceInterface;
use App\Services\Courses\Contracts\CourseStructureServiceInterface;
use App\Services\Courses\Contracts\CourseWorkflowServiceInterface;
use App\Services\Courses\CourseAttachmentService;
use App\Services\Courses\CourseService;
use App\Services\Courses\CourseStructureService;
use App\Services\Courses\CourseWorkflowService;
use Illuminate\Support\ServiceProvider;

class CourseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CourseServiceInterface::class, CourseService::class);
        $this->app->bind(CourseStructureServiceInterface::class, CourseStructureService::class);
        $this->app->bind(CourseAttachmentServiceInterface::class, CourseAttachmentService::class);
        $this->app->bind(CourseWorkflowServiceInterface::class, CourseWorkflowService::class);
    }
}
