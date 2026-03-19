<?php

declare(strict_types=1);

namespace App\Services\Assessments\Contracts;

use App\Enums\AIContentTargetType;
use App\Models\Course;
use App\Models\User;

interface MobileCourseAssetServiceInterface
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listForCourse(User $student, int $centerId, Course $course, ?AIContentTargetType $type = null): array;

    /**
     * @return array<string, mixed>
     */
    public function show(User $student, int $centerId, AIContentTargetType $type, int $id): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function trackProgress(User $student, int $centerId, AIContentTargetType $type, int $id, array $payload): array;
}
