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
use App\Services\Contracts\ViewLimitServiceInterface;
use App\Services\Courses\Contracts\CourseInstructorServiceInterface;
use App\Services\Courses\CourseInstructorService;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use App\Services\Devices\DeviceService;
use App\Services\Enrollments\Contracts\EnrollmentServiceInterface;
use App\Services\Enrollments\EnrollmentService;
use App\Services\Instructors\Contracts\InstructorServiceInterface;
use App\Services\Instructors\InstructorService;
use App\Services\Pdfs\AdminPdfQueryService;
use App\Services\Pdfs\Contracts\AdminPdfQueryServiceInterface;
use App\Services\Pdfs\Contracts\PdfAccessServiceInterface;
use App\Services\Pdfs\Contracts\PdfServiceInterface;
use App\Services\Pdfs\Contracts\PdfUploadSessionServiceInterface;
use App\Services\Pdfs\PdfAccessService;
use App\Services\Pdfs\PdfService;
use App\Services\Pdfs\PdfUploadSessionService;
use App\Services\Permissions\Contracts\PermissionServiceInterface;
use App\Services\Permissions\PermissionService;
use App\Services\Playback\Contracts\PlaybackAuthorizationServiceInterface;
use App\Services\Playback\Contracts\PlaybackServiceInterface;
use App\Services\Playback\PlaybackAuthorizationService;
use App\Services\Playback\PlaybackService;
use App\Services\Playback\ViewLimitService;
use App\Services\Roles\Contracts\RoleServiceInterface;
use App\Services\Roles\RoleService;
use App\Services\Sections\Contracts\SectionServiceInterface;
use App\Services\Sections\Contracts\SectionStructureServiceInterface;
use App\Services\Sections\Contracts\SectionWorkflowServiceInterface;
use App\Services\Sections\SectionService;
use App\Services\Sections\SectionStructureService;
use App\Services\Sections\SectionWorkflowService;
use App\Services\Settings\CenterSettingsService;
use App\Services\Settings\Contracts\CenterSettingsServiceInterface;
use App\Services\Settings\Contracts\SettingsResolverServiceInterface;
use App\Services\Settings\SettingsResolverService;
use App\Services\Storage\Contracts\StorageServiceInterface;
use App\Services\Storage\SpacesStorageService;
use App\Services\Videos\AdminVideoQueryService;
use App\Services\Videos\Contracts\AdminVideoQueryServiceInterface;
use App\Services\Videos\Contracts\VideoServiceInterface;
use App\Services\Videos\Contracts\VideoUploadServiceInterface;
use App\Services\Videos\VideoService;
use App\Services\Videos\VideoUploadService;
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
            SectionWorkflowServiceInterface::class => SectionWorkflowService::class,
            EnrollmentServiceInterface::class => EnrollmentService::class,
            CenterServiceInterface::class => CenterService::class,
            CenterSettingsServiceInterface::class => CenterSettingsService::class,
            SettingsResolverServiceInterface::class => SettingsResolverService::class,
            ViewLimitServiceInterface::class => ViewLimitService::class,
            PdfServiceInterface::class => PdfService::class,
            PdfUploadSessionServiceInterface::class => PdfUploadSessionService::class,
            PdfAccessServiceInterface::class => PdfAccessService::class,
            AdminPdfQueryServiceInterface::class => AdminPdfQueryService::class,
            VideoServiceInterface::class => VideoService::class,
            VideoUploadServiceInterface::class => VideoUploadService::class,
            AdminVideoQueryServiceInterface::class => AdminVideoQueryService::class,
            PlaybackServiceInterface::class => PlaybackService::class,
            PlaybackAuthorizationServiceInterface::class => PlaybackAuthorizationService::class,
            RoleServiceInterface::class => RoleService::class,
            PermissionServiceInterface::class => PermissionService::class,
        ];

        foreach ($bindings as $abstract => $implementation) {
            $this->app->bind($abstract, $implementation);
        }

        $this->app->singleton(ViewLimitService::class);
        $this->app->singleton(ViewLimitServiceInterface::class, ViewLimitService::class);
        $this->app->singleton(StorageServiceInterface::class, function (Application $app): StorageServiceInterface {
            $diskName = (string) $app['config']->get('filesystems.default', 'local');

            return new SpacesStorageService($app['filesystem']->disk($diskName));
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
