<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Exceptions\CenterMismatchException;
use App\Models\User;

class StudentProfileQueryService
{
    public function load(User $student): User
    {
        $student->load([
            'center',
            'studentSetting',
            'enrollments' => function ($query): void {
                $query->with([
                    'course' => function ($query): void {
                        $query->with([
                            'sections' => function ($query): void {
                                $query->orderBy('order_index')
                                    ->with([
                                        'videos' => function ($query): void {
                                            $query->orderByPivot('order_index');
                                        },
                                    ]);
                            },
                            'setting',
                            'center',
                        ]);
                    },
                ]);
            },
            'playbackSessions' => function ($query): void {
                $query->select('id', 'user_id', 'video_id', 'course_id', 'progress_percent', 'is_full_play')
                    ->notDeleted();
            },
        ]);

        return $student;
    }

    public function assertMatchesResolvedCenterScope(User $student, ?int $resolvedCenterId): void
    {
        if ($resolvedCenterId === null) {
            return;
        }

        if (! is_numeric($student->center_id) || (int) $student->center_id !== $resolvedCenterId) {
            throw new CenterMismatchException('Student does not belong to this center.');
        }
    }
}
