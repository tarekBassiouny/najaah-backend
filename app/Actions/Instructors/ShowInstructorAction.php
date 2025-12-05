<?php

declare(strict_types=1);

namespace App\Actions\Instructors;

use App\Models\Instructor;

class ShowInstructorAction
{
    public function execute(Instructor $instructor): Instructor
    {
        return $instructor->loadMissing(['center', 'creator']);
    }
}
