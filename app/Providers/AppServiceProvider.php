<?php

namespace App\Providers;

use App\Services\Auth\AdminAuthService;
use App\Services\Auth\Contracts\AdminAuthServiceInterface;
use App\Services\Auth\Contracts\JwtServiceInterface;
use App\Services\Auth\Contracts\OtpServiceInterface;
use App\Services\Auth\JwtService;
use App\Services\Auth\OtpService;
use App\Services\Centers\CenterService;
use App\Services\Centers\Contracts\CenterServiceInterface;
use App\Services\Courses\Contracts\CourseInstructorServiceInterface;
use App\Services\Courses\CourseInstructorService;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use App\Services\Devices\DeviceService;
use App\Services\Enrollments\Contracts\EnrollmentServiceInterface;
use App\Services\Enrollments\EnrollmentService;
use App\Services\Instructors\Contracts\InstructorServiceInterface;
use App\Services\Instructors\InstructorService;
use App\Services\Playback\ConcurrencyService;
use App\Services\Playback\PlaybackAuthorizationService;
use App\Services\Playback\PlaybackSessionService;
use App\Services\Playback\ViewLimitService;
use App\Services\Sections\Contracts\SectionServiceInterface;
use App\Services\Sections\SectionService;
use App\Services\Settings\CenterSettingsService;
use App\Services\Settings\Contracts\CenterSettingsServiceInterface;
use App\Services\Settings\Contracts\SettingsResolverServiceInterface;
use App\Services\Settings\SettingsResolverService;
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
            EnrollmentServiceInterface::class => EnrollmentService::class,
            CenterServiceInterface::class => CenterService::class,
            CenterSettingsServiceInterface::class => CenterSettingsService::class,
            SettingsResolverServiceInterface::class => SettingsResolverService::class,
        ];

        foreach ($bindings as $abstract => $implementation) {
            $this->app->bind($abstract, $implementation);
        }

        $this->app->singleton(PlaybackSessionService::class);
        $this->app->singleton(PlaybackAuthorizationService::class);
        $this->app->singleton(ViewLimitService::class);
        $this->app->singleton(ConcurrencyService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
