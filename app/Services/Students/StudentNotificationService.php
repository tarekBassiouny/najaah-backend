<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\Enrollment;
use App\Models\User;
use App\Services\Auth\Contracts\OtpSenderInterface;
use App\Services\Logging\LogContextResolver;
use App\Services\Students\Contracts\StudentNotificationServiceInterface;
use Illuminate\Support\Facades\Log;

class StudentNotificationService implements StudentNotificationServiceInterface
{
    public function __construct(
        private readonly OtpSenderInterface $sender,
        private readonly LogContextResolver $logContextResolver
    ) {}

    public function sendWelcomeMessage(User $student): bool
    {
        $message = $this->formatWelcomeMessage($student);
        $destination = $student->country_code.$student->phone;

        try {
            $this->sender->send($destination, $message);

            Log::info('Welcome message sent to student.', $this->logContextResolver->resolve([
                'source' => 'api',
                'student_id' => $student->id,
                'center_id' => $student->center_id,
            ]));

            return true;
        } catch (\Throwable $throwable) {
            Log::error('Failed to send welcome message.', $this->logContextResolver->resolve([
                'source' => 'api',
                'student_id' => $student->id,
                'center_id' => $student->center_id,
                'error' => $throwable->getMessage(),
            ]));

            return false;
        }
    }

    public function sendEnrollmentNotification(Enrollment $enrollment): bool
    {
        $student = $enrollment->user;
        $message = $this->formatEnrollmentMessage($enrollment);
        $destination = $student->country_code.$student->phone;

        try {
            $this->sender->send($destination, $message);

            Log::info('Enrollment notification sent to student.', $this->logContextResolver->resolve([
                'source' => 'api',
                'student_id' => $student->id,
                'enrollment_id' => $enrollment->id,
                'course_id' => $enrollment->course_id,
                'center_id' => $enrollment->center_id,
            ]));

            return true;
        } catch (\Throwable $throwable) {
            Log::error('Failed to send enrollment notification.', $this->logContextResolver->resolve([
                'source' => 'api',
                'student_id' => $student->id,
                'enrollment_id' => $enrollment->id,
                'course_id' => $enrollment->course_id,
                'center_id' => $enrollment->center_id,
                'error' => $throwable->getMessage(),
            ]));

            return false;
        }
    }

    private function formatWelcomeMessage(User $student): string
    {
        $template = (string) config('notifications.student.templates.welcome');
        $centerName = $student->center?->name ?? 'XYZ LMS';

        return strtr($template, [
            '{center_name}' => $centerName,
            '{ios_link}' => (string) config('notifications.student.app_links.ios'),
            '{android_link}' => (string) config('notifications.student.app_links.android'),
            '{phone}' => $student->phone,
        ]);
    }

    private function formatEnrollmentMessage(Enrollment $enrollment): string
    {
        $template = (string) config('notifications.student.templates.enrollment');
        $courseName = $enrollment->course->translate('title');
        $centerName = $enrollment->user->center?->name ?? 'XYZ LMS';

        return strtr($template, [
            '{course_name}' => $courseName,
            '{center_name}' => $centerName,
        ]);
    }
}
