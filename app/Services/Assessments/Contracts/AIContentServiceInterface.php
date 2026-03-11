<?php

declare(strict_types=1);

namespace App\Services\Assessments\Contracts;

use App\Models\AIContentJob;
use App\Models\Center;
use App\Models\User;

interface AIContentServiceInterface
{
    /**
     * @param  array<string,mixed>  $data
     */
    public function createJob(Center $center, array $data, User $creator): AIContentJob;

    public function processJob(AIContentJob $job): void;

    /**
     * @param  array<string,mixed>  $reviewedPayload
     */
    public function reviewJob(AIContentJob $job, array $reviewedPayload, User $reviewer): AIContentJob;

    public function approveJob(AIContentJob $job, User $reviewer): AIContentJob;

    /**
     * @return array<string,mixed>
     */
    public function publishJob(AIContentJob $job, User $publisher): array;

    public function discardJob(AIContentJob $job): void;

    /**
     * @return array<string,mixed>
     */
    public function getJobStatus(AIContentJob $job): array;
}
