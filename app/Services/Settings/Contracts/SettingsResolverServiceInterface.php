<?php

declare(strict_types=1);

namespace App\Services\Settings\Contracts;

use App\Models\Center;
use App\Models\Course;
use App\Models\User;
use App\Models\Video;

interface SettingsResolverServiceInterface
{
    /**
     * Resolve effective settings using priority Student → Video → Course → Center.
     *
     * @return array<string, mixed>
     */
    public function resolve(?User $student, ?Video $video = null, ?Course $course = null, ?Center $center = null): array;
}
