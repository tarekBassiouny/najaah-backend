<?php

declare(strict_types=1);

namespace App\Services\Students\Contracts;

use App\Models\Enrollment;
use App\Models\User;

interface StudentNotificationServiceInterface
{
    /**
     * Send welcome message with app download links to a newly created student.
     */
    public function sendWelcomeMessage(User $student): bool;

    /**
     * Send enrollment notification to a student.
     */
    public function sendEnrollmentNotification(Enrollment $enrollment): bool;
}
