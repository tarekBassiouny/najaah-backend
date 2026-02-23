<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Enrollment;
use App\Models\Pivots\UserCenter;

class EnrollmentObserver
{
    /**
     * Handle the Enrollment "created" event.
     *
     * Automatically associates the student with the enrollment's center
     * via the user_centers pivot table.
     */
    public function created(Enrollment $enrollment): void
    {
        $this->syncStudentCenterAssociation($enrollment);
    }

    /**
     * Handle the Enrollment "restored" event.
     *
     * Re-associates the student with the center when enrollment is restored.
     */
    public function restored(Enrollment $enrollment): void
    {
        $this->syncStudentCenterAssociation($enrollment);
    }

    /**
     * Sync student-center association in user_centers pivot.
     *
     * This ensures the student appears in the center's student list
     * once they have an enrollment in that center's courses.
     */
    private function syncStudentCenterAssociation(Enrollment $enrollment): void
    {
        $userId = $enrollment->user_id;
        $centerId = $enrollment->center_id;

        if (! is_numeric($userId) || ! is_numeric($centerId)) {
            return;
        }

        // Check if association already exists (including soft-deleted)
        $existing = UserCenter::withTrashed()
            ->where('user_id', $userId)
            ->where('center_id', $centerId)
            ->where('type', 'student')
            ->first();

        if ($existing !== null) {
            // Restore if soft-deleted
            if ($existing->trashed()) {
                $existing->restore();
            }

            return;
        }

        // Create new association
        UserCenter::create([
            'user_id' => (int) $userId,
            'center_id' => (int) $centerId,
            'type' => 'student',
        ]);
    }
}
