<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class)->group('auth', 'center-isolation');

it('blocks student access when center does not match', function (): void {
    $centerA = Center::factory()->create();
    $centerB = Center::factory()->create();

    $student = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
        'center_id' => $centerA->id,
    ]);
    $student->centers()->syncWithoutDetaching([
        $centerA->id => ['type' => 'student'],
    ]);

    $course = Course::factory()->create([
        'status' => 3,
        'center_id' => $centerB->id,
    ]);

    Enrollment::factory()->create([
        'user_id' => $student->id,
        'course_id' => $course->id,
        'center_id' => $course->center_id,
        'status' => Enrollment::STATUS_ACTIVE,
    ]);

    $token = Auth::guard('api')->login($student);
    $this->asApiUser($student, $token, 'device-abc');

    $response = $this->apiGet("/api/v1/courses/{$course->id}");

    $response->assertStatus(403)->assertJsonPath('error.code', 'CENTER_MISMATCH');
});

it('allows system-level students without center assignment', function (): void {
    $student = User::factory()->create([
        'is_student' => true,
        'password' => 'secret123',
        'center_id' => null,
    ]);

    $token = Auth::guard('api')->login($student);
    $this->asApiUser($student, $token, 'device-xyz');

    $response = $this->apiGet('/api/v1/auth/me');

    $response->assertOk()->assertJsonPath('data.center_id', null);
});
