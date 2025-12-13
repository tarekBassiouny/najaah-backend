<?php

declare(strict_types=1);

use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('enrollments', 'api');

it('lists only the authenticated student enrollments', function (): void {
    $student = $this->makeApiUser();
    $this->asApiUser($student);

    $otherStudent = $this->makeApiUser();

    $courseOne = $this->createCourse(['status' => 3]);
    $courseTwo = $this->createCourse(['status' => 3]);
    $otherCourse = $this->createCourse(['status' => 3]);

    $this->enrollStudent($student, $courseOne, Enrollment::STATUS_ACTIVE);
    $this->enrollStudent($student, $courseTwo, Enrollment::STATUS_DEACTIVATED);
    $this->enrollStudent($otherStudent, $otherCourse, Enrollment::STATUS_ACTIVE);

    $response = $this->apiGet('/api/v1/enrollments');

    $response->assertOk()
        ->assertJsonPath('meta.total', 2);
});
