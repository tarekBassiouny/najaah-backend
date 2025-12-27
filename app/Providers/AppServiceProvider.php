<?php

namespace App\Providers;

use App\Services\Auth\AdminAuthService;
use App\Services\Auth\Contracts\AdminAuthServiceInterface;
use App\Services\Auth\Contracts\JwtServiceInterface;
use App\Services\Auth\Contracts\OtpSenderInterface;
use App\Services\Auth\Contracts\OtpServiceInterface;
use App\Services\Auth\JwtService;
use App\Services\Auth\OtpService;
use App\Services\Auth\Senders\WhatsAppOtpSender;
use App\Services\Bunny\BunnyLibraryService;
use App\Services\Bunny\BunnyStreamService;
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
use App\Services\Playback\ViewLimitService;
use App\Services\Sections\Contracts\SectionServiceInterface;
use App\Services\Sections\Contracts\SectionStructureServiceInterface;
use App\Services\Sections\SectionService;
use App\Services\Sections\SectionStructureService;
use App\Services\Settings\CenterSettingsService;
use App\Services\Settings\Contracts\CenterSettingsServiceInterface;
use App\Services\Settings\Contracts\SettingsResolverServiceInterface;
use App\Services\Settings\SettingsResolverService;
use App\Services\Storage\Contracts\StorageServiceInterface;
use App\Services\Storage\SpacesStorageService;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $bindings = [
            OtpServiceInterface::class => OtpService::class,
            OtpSenderInterface::class => WhatsAppOtpSender::class,
            JwtServiceInterface::class => JwtService::class,
            DeviceServiceInterface::class => DeviceService::class,
            AdminAuthServiceInterface::class => AdminAuthService::class,
            InstructorServiceInterface::class => InstructorService::class,
            CourseInstructorServiceInterface::class => CourseInstructorService::class,
            SectionServiceInterface::class => SectionService::class,
            SectionStructureServiceInterface::class => SectionStructureService::class,
            EnrollmentServiceInterface::class => EnrollmentService::class,
            CenterServiceInterface::class => CenterService::class,
            CenterSettingsServiceInterface::class => CenterSettingsService::class,
            SettingsResolverServiceInterface::class => SettingsResolverService::class,
        ];

        foreach ($bindings as $abstract => $implementation) {
            $this->app->bind($abstract, $implementation);
        }

        $this->app->singleton(ViewLimitService::class);
        $this->app->singleton(StorageServiceInterface::class, function (Application $app): StorageServiceInterface {
            $disk = (string) $app['config']->get('filesystems.default', 'local');

            return new SpacesStorageService($app['filesystem']->disk($disk));
        });

        $this->app->singleton(BunnyStreamService::class, function (Application $app): BunnyStreamService {
            $apiConfig = $app['config']->get('bunny.api', []);
            $apiKey = (string) ($apiConfig['api_key'] ?? '');
            $apiUrl = (string) ($apiConfig['api_url'] ?? '');
            $libraryId = (string) ($apiConfig['library_id'] ?? '');

            if ($apiKey === '' || $apiUrl === '') {
                throw new RuntimeException('Missing Bunny Stream configuration.');
            }

            return new BunnyStreamService(
                apiKey: $apiKey,
                apiUrl: $apiUrl,
                libraryId: $libraryId,
            );
        });

        $this->app->singleton(BunnyLibraryService::class, function (Application $app): BunnyLibraryService {
            $apiConfig = $app['config']->get('bunny.api', []);
            $apiKey = (string) ($apiConfig['api_key'] ?? '');
            $apiUrl = (string) ($apiConfig['api_url'] ?? '');

            if ($apiKey === '' || $apiUrl === '') {
                throw new RuntimeException('Missing Bunny Stream configuration.');
            }

            return new BunnyLibraryService(
                apiKey: $apiKey,
                apiUrl: $apiUrl,
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
