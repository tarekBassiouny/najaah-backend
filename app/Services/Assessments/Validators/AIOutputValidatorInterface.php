<?php

declare(strict_types=1);

namespace App\Services\Assessments\Validators;

use App\Models\AIContentJob;

interface AIOutputValidatorInterface
{
    /**
     * @param  array<string,mixed>  $payload
     * @return array<int,string>
     */
    public function validate(AIContentJob $job, array $payload): array;
}
