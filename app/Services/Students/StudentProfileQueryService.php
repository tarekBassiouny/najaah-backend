<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\CourseAccessModel;
use App\Enums\DeviceChangeRequestStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\UserDeviceStatus;
use App\Exceptions\CenterMismatchException;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class StudentProfileQueryService
{
    public function load(User $student): User
    {
        $student->load([
            'center',
            'grade',
            'school',
            'college',
            'studentSetting',
            'devices' => function ($query): void {
                $query->where('status', UserDeviceStatus::Active->value)
                    ->orderByDesc('last_used_at')
                    ->limit(1);
            },
            'deviceChangeRequests' => function ($query): void {
                $query->where('status', DeviceChangeRequestStatus::Approved->value)
                    ->orderByDesc('decided_at');
            },
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
                            'learningAssets' => function ($query): void {
                                $query->published()
                                    ->orderByDesc('published_at')
                                    ->orderByDesc('id');
                            },
                            'setting',
                            'center',
                        ]);
                    },
                ]);
            },
            'playbackSessions' => function ($query): void {
                $query->select('id', 'user_id', 'video_id', 'course_id', 'progress_percent', 'is_full_play', 'updated_at')
                    ->notDeleted();
            },
            'videoAccesses' => function ($query): void {
                $query->select(
                    'id',
                    'user_id',
                    'video_id',
                    'course_id',
                    'center_id',
                    'total_view_limit',
                    'granted_at',
                    'revoked_at'
                )->active();
            },
            'learningAssetProgressRecords' => function ($query): void {
                $query->select(
                    'id',
                    'user_id',
                    'learning_asset_id',
                    'course_id',
                    'status',
                    'progress_percent',
                    'last_interacted_at',
                    'completed_at'
                );
            },
        ]);

        $accessibleCourses = Course::query()
            ->where(function (Builder $query) use ($student): void {
                $query->where(function (Builder $enrollmentQuery) use ($student): void {
                    $enrollmentQuery
                        ->where('access_model', CourseAccessModel::Enrollment->value)
                        ->whereHas('enrollments', function (Builder $query) use ($student): void {
                            $query->where('user_id', $student->id)
                                ->where('status', EnrollmentStatus::Active->value)
                                ->whereNull('deleted_at');
                        });
                })->orWhere(function (Builder $videoCodeQuery) use ($student): void {
                    $videoCodeQuery
                        ->where('access_model', CourseAccessModel::VideoCode->value)
                        ->whereHas('videoAccesses', function (Builder $query) use ($student): void {
                            $query->where('user_id', $student->id)
                                ->whereNull('revoked_at')
                                ->whereNull('deleted_at');
                        });
                });
            })
            ->with([
                'sections' => function ($query): void {
                    $query->orderBy('order_index')
                        ->with([
                            'videos' => function ($query): void {
                                $query->orderByPivot('order_index');
                            },
                        ]);
                },
                'learningAssets' => function ($query): void {
                    $query->published()
                        ->orderByDesc('published_at')
                        ->orderByDesc('id');
                },
                'setting',
                'center',
            ])
            ->orderByDesc('id')
            ->get();

        $student->setRelation('accessibleCourses', $accessibleCourses);

        return $student;
    }

    public function assertMatchesResolvedCenterScope(User $student, ?int $resolvedCenterId): void
    {
        if ($resolvedCenterId === null) {
            if (is_numeric($student->center_id)) {
                throw new CenterMismatchException('Student does not belong to system scope.');
            }

            return;
        }

        if (! is_numeric($student->center_id) || (int) $student->center_id !== $resolvedCenterId) {
            throw new CenterMismatchException('Student does not belong to this center.');
        }
    }
}
