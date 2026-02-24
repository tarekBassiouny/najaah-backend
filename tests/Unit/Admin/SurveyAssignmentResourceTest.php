<?php

declare(strict_types=1);

use App\Enums\SurveyAssignableType;
use App\Http\Resources\Admin\SurveyAssignmentResource;
use App\Models\Center;
use App\Models\Course;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns localized assignable_name for course assignments', function (): void {
    config(['app.fallback_locale' => 'en']);
    app()->setLocale('ar');

    $course = Course::factory()->create([
        'title_translations' => [
            'en' => 'Physics Course',
            'ar' => 'دورة الفيزياء',
        ],
    ]);

    $survey = Survey::factory()->create(['center_id' => $course->center_id]);

    $assignment = SurveyAssignment::factory()->create([
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::Course,
        'assignable_id' => $course->id,
    ]);

    $payload = (new SurveyAssignmentResource($assignment))
        ->toArray(Request::create('/', 'GET'));

    expect($payload['assignable_name'])->toBe('دورة الفيزياء');
});

it('returns localized assignable_name for center assignments', function (): void {
    config(['app.fallback_locale' => 'en']);
    app()->setLocale('ar');

    $center = Center::factory()->create([
        'name_translations' => [
            'en' => 'Main Center',
            'ar' => 'المركز الرئيسي',
        ],
    ]);

    $survey = Survey::factory()->create(['center_id' => $center->id]);

    $assignment = SurveyAssignment::factory()->create([
        'survey_id' => $survey->id,
        'assignable_type' => SurveyAssignableType::Center,
        'assignable_id' => $center->id,
    ]);

    $payload = (new SurveyAssignmentResource($assignment))
        ->toArray(Request::create('/', 'GET'));

    expect($payload['assignable_name'])->toBe('المركز الرئيسي');
});
