<?php

namespace App\Providers;

use App\Services\Auth\AdminAuthService;
use App\Services\Auth\Contracts\AdminAuthServiceInterface;
use App\Services\Auth\Contracts\JwtServiceInterface;
use App\Services\Auth\Contracts\OtpServiceInterface;
use App\Services\Auth\JwtService;
use App\Services\Auth\OtpService;
use App\Services\Courses\Contracts\CourseInstructorServiceInterface;
use App\Services\Courses\CourseInstructorService;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use App\Services\Devices\DeviceService;
use App\Services\Instructors\Contracts\InstructorServiceInterface;
use App\Services\Instructors\InstructorService;
use App\Services\Sections\Contracts\SectionServiceInterface;
use App\Services\Sections\SectionService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $bindings = [
            OtpServiceInterface::class => OtpService::class,
            JwtServiceInterface::class => JwtService::class,
            DeviceServiceInterface::class => DeviceService::class,
            AdminAuthServiceInterface::class => AdminAuthService::class,
            InstructorServiceInterface::class => InstructorService::class,
            CourseInstructorServiceInterface::class => CourseInstructorService::class,
            SectionServiceInterface::class => SectionService::class,
        ];

        foreach ($bindings as $abstract => $implementation) {
            $this->app->bind($abstract, $implementation);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
