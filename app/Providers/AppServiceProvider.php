<?php

namespace App\Providers;

use App\Services\AdminNotifications\AdminNotificationService;
use App\Services\AdminNotifications\Contracts\AdminNotificationServiceInterface;
use App\Services\AdminUsers\AdminUserService;
use App\Services\AdminUsers\Contracts\AdminUserServiceInterface;
use App\Services\Auth\AdminAuthService;
use App\Services\Auth\Contracts\AdminAuthServiceInterface;
use App\Services\Auth\Contracts\JwtServiceInterface;
use App\Services\Auth\Contracts\OtpSenderInterface;
use App\Services\Auth\Contracts\OtpServiceInterface;
use App\Services\Auth\JwtService;
use App\Services\Auth\OtpService;
use App\Services\Auth\Senders\EvolutionOtpSender;
use App\Services\Auth\Senders\WhatsAppOtpSender;
use App\Services\Bunny\BunnyLibraryService;
use App\Services\Bunny\BunnyStreamService;
use App\Services\Centers\CenterScopeService;
use App\Services\Centers\CenterService;
use App\Services\Centers\Contracts\CenterScopeServiceInterface;
use App\Services\Centers\Contracts\CenterServiceInterface;
use App\Services\Courses\Contracts\CourseInstructorServiceInterface;
use App\Services\Courses\CourseInstructorService;
use App\Services\Dashboard\Contracts\DashboardServiceInterface;
use App\Services\Dashboard\DashboardService;
use App\Services\Devices\Contracts\DeviceChangeServiceInterface;
use App\Services\Devices\Contracts\DeviceServiceInterface;
use App\Services\Devices\DeviceChangeService;
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
use App\Services\Playback\Contracts\ViewLimitServiceInterface;
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
use App\Services\Settings\Contracts\SystemSettingServiceInterface;
use App\Services\Settings\SettingsResolverService;
use App\Services\Settings\SystemSettingService;
use App\Services\Storage\Contracts\StorageServiceInterface;
use App\Services\Storage\SpacesStorageService;
use App\Services\Students\Contracts\StudentNotificationServiceInterface;
use App\Services\Students\StudentNotificationService;
use App\Services\Surveys\Contracts\SurveyAssignmentServiceInterface;
use App\Services\Surveys\Contracts\SurveyResponseServiceInterface;
use App\Services\Surveys\Contracts\SurveyServiceInterface;
use App\Services\Surveys\SurveyAssignmentService;
use App\Services\Surveys\SurveyResponseService;
use App\Services\Surveys\SurveyService;
use App\Services\Videos\AdminVideoQueryService;
use App\Services\Videos\Contracts\AdminVideoQueryServiceInterface;
use App\Services\Videos\Contracts\VideoServiceInterface;
use App\Services\Videos\Contracts\VideoUploadServiceInterface;
use App\Services\Videos\VideoService;
use App\Services\Videos\VideoUploadService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Queue\Job as QueueJobContract;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @var array<string, float>
     */
    private static array $queueJobStartedAt = [];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $bindings = [
            AdminNotificationServiceInterface::class => AdminNotificationService::class,
            AdminUserServiceInterface::class => AdminUserService::class,
            OtpServiceInterface::class => OtpService::class,
            JwtServiceInterface::class => JwtService::class,
            DeviceServiceInterface::class => DeviceService::class,
            DeviceChangeServiceInterface::class => DeviceChangeService::class,
            DashboardServiceInterface::class => DashboardService::class,
            AdminAuthServiceInterface::class => AdminAuthService::class,
            InstructorServiceInterface::class => InstructorService::class,
            CourseInstructorServiceInterface::class => CourseInstructorService::class,
            SectionServiceInterface::class => SectionService::class,
            SectionStructureServiceInterface::class => SectionStructureService::class,
            SectionWorkflowServiceInterface::class => SectionWorkflowService::class,
            EnrollmentServiceInterface::class => EnrollmentService::class,
            CenterServiceInterface::class => CenterService::class,
            CenterScopeServiceInterface::class => CenterScopeService::class,
            CenterSettingsServiceInterface::class => CenterSettingsService::class,
            SettingsResolverServiceInterface::class => SettingsResolverService::class,
            SystemSettingServiceInterface::class => SystemSettingService::class,
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
            StudentNotificationServiceInterface::class => StudentNotificationService::class,
            SurveyServiceInterface::class => SurveyService::class,
            SurveyAssignmentServiceInterface::class => SurveyAssignmentService::class,
            SurveyResponseServiceInterface::class => SurveyResponseService::class,
        ];

        foreach ($bindings as $abstract => $implementation) {
            $this->app->bind($abstract, $implementation);
        }

        $this->app->bind(OtpSenderInterface::class, function (Application $app): OtpSenderInterface {
            $sender = (string) $app['config']->get('otp.sender', 'whatsapp');

            return match ($sender) {
                'evolution' => $app->make(EvolutionOtpSender::class),
                'whatsapp' => $app->make(WhatsAppOtpSender::class),
                default => throw new RuntimeException(sprintf('Unsupported OTP sender [%s].', $sender)),
            };
        });

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
        RateLimiter::for('otp-send', static function (Request $request): Limit {
            $phone = (string) $request->input('phone', '');

            return Limit::perMinute(5)->by($request->ip().'|'.$phone);
        });

        RateLimiter::for('otp-verify', static function (Request $request): Limit {
            $token = (string) $request->input('token', '');

            return Limit::perMinute(10)->by($request->ip().'|'.$token);
        });

        RateLimiter::for('admin-login', static function (Request $request): Limit {
            $email = (string) $request->input('email', '');

            return Limit::perMinute(5)->by($request->ip().'|'.$email);
        });

        RateLimiter::for('admin-refresh', static function (Request $request): Limit {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('admin-forgot', static function (Request $request): Limit {
            $email = (string) $request->input('email', '');

            return Limit::perMinute(5)->by($request->ip().'|'.$email);
        });

        $this->registerQueuePayloadContext();
        $this->registerQueueLifecycleLogging();
    }

    private function registerQueuePayloadContext(): void
    {
        Queue::createPayloadUsing(static function (?string $connection, ?string $queue, array $payload): array {
            $request = app()->bound('request') ? app('request') : null;
            if (! $request instanceof Request) {
                return [];
            }

            $requestId = $request->headers->get('X-Request-Id')
                ?? $request->attributes->get('request_id');

            if (! is_string($requestId) || $requestId === '') {
                return [];
            }

            return [
                'meta' => [
                    'request_id' => $requestId,
                ],
            ];
        });
    }

    private function registerQueueLifecycleLogging(): void
    {
        if (! (bool) config('logging.job_logging.enabled', true)) {
            return;
        }

        $channel = (string) config('logging.job_logging.channel', 'jobs');

        Queue::before(function (JobProcessing $event) use ($channel): void {
            $identifier = $this->jobIdentifier($event->job);
            self::$queueJobStartedAt[$identifier] = microtime(true);

            Log::channel($channel)->info('job_processing', $this->buildJobLogContext(
                $event->job,
                $event->connectionName,
                'processing',
                null
            ));
        });

        Queue::after(function (JobProcessed $event) use ($channel): void {
            Log::channel($channel)->info('job_processed', $this->buildJobLogContext(
                $event->job,
                $event->connectionName,
                'processed',
                $this->jobDurationMs($event->job)
            ));
        });

        Queue::failing(function (JobFailed $event) use ($channel): void {
            $context = $this->buildJobLogContext(
                $event->job,
                $event->connectionName,
                'failed',
                $this->jobDurationMs($event->job)
            );
            $context['exception_class'] = $event->exception::class;
            $context['exception_message'] = $event->exception->getMessage();

            Log::channel($channel)->error('job_failed', $context);
        });
    }

    /**
     * @return array{
     *   status:string,
     *   connection:string,
     *   queue:string|null,
     *   job_name:string,
     *   job_id:string|int|null,
     *   job_uuid:string|null,
     *   attempts:int,
     *   request_id:string|null,
     *   duration_ms:int|null
     * }
     */
    private function buildJobLogContext(
        QueueJobContract $job,
        string $connectionName,
        string $status,
        ?int $durationMs
    ): array {
        $payload = $this->jobPayload($job);
        $requestId = data_get($payload, 'meta.request_id');
        $jobUuid = data_get($payload, 'uuid');
        $queue = method_exists($job, 'getQueue') ? $job->getQueue() : null;
        $jobName = method_exists($job, 'resolveName') ? $job->resolveName() : $job::class;
        $jobId = method_exists($job, 'getJobId') ? $job->getJobId() : null;
        $attempts = method_exists($job, 'attempts') ? (int) $job->attempts() : 0;

        return [
            'status' => $status,
            'connection' => $connectionName,
            'queue' => $queue,
            'job_name' => $jobName,
            'job_id' => $jobId,
            'job_uuid' => is_string($jobUuid) && $jobUuid !== '' ? $jobUuid : null,
            'attempts' => $attempts,
            'request_id' => is_string($requestId) && $requestId !== '' ? $requestId : null,
            'duration_ms' => $durationMs,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function jobPayload(QueueJobContract $job): array
    {
        if (! method_exists($job, 'payload')) {
            return [];
        }

        try {
            $payload = $job->payload();
        } catch (\Throwable) {
            return [];
        }

        return is_array($payload) ? $payload : [];
    }

    private function jobIdentifier(QueueJobContract $job): string
    {
        $jobId = method_exists($job, 'getJobId') ? $job->getJobId() : null;
        if (is_string($jobId) && $jobId !== '') {
            return $jobId;
        }

        return spl_object_hash($job);
    }

    private function jobDurationMs(QueueJobContract $job): ?int
    {
        $identifier = $this->jobIdentifier($job);
        $startedAt = self::$queueJobStartedAt[$identifier] ?? null;
        unset(self::$queueJobStartedAt[$identifier]);

        if (! is_int($startedAt) && ! is_float($startedAt)) {
            return null;
        }

        return (int) round((microtime(true) - (float) $startedAt) * 1000);
    }
}
