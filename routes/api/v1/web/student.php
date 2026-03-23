<?php

declare(strict_types=1);

use App\Http\Controllers\Mobile\AssignmentGroupController;
use App\Http\Controllers\Mobile\AssignmentSubmissionController;
use App\Http\Controllers\Mobile\CategoryController;
use App\Http\Controllers\Mobile\CentersController;
use App\Http\Controllers\Mobile\CourseAssetController;
use App\Http\Controllers\Mobile\Education\CollegeLookupController;
use App\Http\Controllers\Mobile\Education\EducationLookupController;
use App\Http\Controllers\Mobile\Education\GradeLookupController;
use App\Http\Controllers\Mobile\Education\SchoolLookupController;
use App\Http\Controllers\Mobile\EnrolledCoursesController;
use App\Http\Controllers\Mobile\EnrollmentRequestController;
use App\Http\Controllers\Mobile\ExploreController;
use App\Http\Controllers\Mobile\ExtraViewRequestController;
use App\Http\Controllers\Mobile\InstructorController;
use App\Http\Controllers\Mobile\PdfController;
use App\Http\Controllers\Mobile\PlaybackController;
use App\Http\Controllers\Mobile\QuizAttemptController;
use App\Http\Controllers\Mobile\SearchController;
use App\Http\Controllers\Mobile\SurveyController;
use App\Http\Controllers\Mobile\VideoAccessCodeController;
use App\Http\Controllers\Mobile\VideoAccessRequestController;
use App\Http\Controllers\Mobile\WeeklyActivityController;
use App\Http\Controllers\Web\Student\MeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest-Accessible Browsing Routes (Optional Authentication)
|--------------------------------------------------------------------------
| These routes allow guest browsing when the center has allow_guest_browsing
| enabled. Authenticated users can always access these routes.
*/
Route::middleware(['jwt.web.student.optional', 'guest.browsing'])->group(function (): void {

    /*
    |--------------------------------------------------------------------------
    | Explore
    |--------------------------------------------------------------------------
    */
    Route::get('/courses/explore', [ExploreController::class, 'explore']);
    Route::get('/centers/{center}/courses/{course}', [ExploreController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */
    Route::get('/search', [SearchController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Centers (Unbranded Only)
    |--------------------------------------------------------------------------
    */
    Route::get('/centers', [CentersController::class, 'index']);
    Route::get('/centers/{center}', [CentersController::class, 'show']);
    Route::get('/centers/{center}/categories', [CategoryController::class, 'centerIndex']);

    /*
    |--------------------------------------------------------------------------
    | Instructors
    |--------------------------------------------------------------------------
    */
    Route::get('/instructors', [InstructorController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    */
    Route::get('/categories', [CategoryController::class, 'index']);

});

/*
|--------------------------------------------------------------------------
| Authenticated Web Student Routes
|--------------------------------------------------------------------------
*/
Route::middleware('jwt.web.student')->group(function (): void {

    /*
    |--------------------------------------------------------------------------
    | Profile & Auth
    |--------------------------------------------------------------------------
    */
    Route::get('/auth/me', [MeController::class, 'profile']);
    Route::get('/auth/me/profile', [MeController::class, 'profileDetails']);
    Route::post('/auth/me', [MeController::class, 'updateProfile']);
    Route::patch('/auth/me/education', [MeController::class, 'updateEducation']);
    Route::post('/auth/logout', [MeController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | My Courses (Enrolled)
    |--------------------------------------------------------------------------
    */
    Route::get('/courses/enrolled', [EnrolledCoursesController::class, 'index']);
    Route::get('/courses/enrolled/by-instructor', [EnrolledCoursesController::class, 'byInstructor']);
    Route::get('/centers/{center}/activity/weekly', [WeeklyActivityController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Playback
    |--------------------------------------------------------------------------
    */
    Route::post(
        '/centers/{center}/courses/{course}/videos/{video}/request_playback',
        [PlaybackController::class, 'requestPlayback']
    );
    Route::post(
        '/centers/{center}/courses/{course}/videos/{video}/refresh_token',
        [PlaybackController::class, 'refreshToken']
    );
    Route::post(
        '/centers/{center}/courses/{course}/videos/{video}/playback_progress',
        [PlaybackController::class, 'updateProgress']
    );
    Route::post(
        '/centers/{center}/courses/{course}/videos/{video}/close_session',
        [PlaybackController::class, 'closeSession']
    );

    /*
    |--------------------------------------------------------------------------
    | PDFs
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/centers/{center}/courses/{course}/pdfs/{pdf}/signed-url',
        [PdfController::class, 'signedUrl']
    );

    /*
    |--------------------------------------------------------------------------
    | Extra View Requests
    |--------------------------------------------------------------------------
    */
    Route::post(
        '/centers/{center}/courses/{course}/videos/{video}/extra-view',
        [ExtraViewRequestController::class, 'store']
    );

    /*
    |--------------------------------------------------------------------------
    | Video Access Approval
    |--------------------------------------------------------------------------
    */
    Route::post(
        '/centers/{center}/courses/{course}/videos/{video}/access-request',
        [VideoAccessRequestController::class, 'store']
    );
    Route::get(
        '/centers/{center}/courses/{course}/videos/{video}/access-status',
        [VideoAccessRequestController::class, 'status']
    );
    Route::post('/video-access-codes/redeem', [VideoAccessCodeController::class, 'redeem']);

    /*
    |--------------------------------------------------------------------------
    | Video Code Batches (for video_code access model)
    |--------------------------------------------------------------------------
    */
    Route::post('/video-codes/redeem', [VideoAccessCodeController::class, 'redeemBatchCode'])
        ->middleware('throttle:video-code-redeem');
    Route::post('/video-codes/validate', [VideoAccessCodeController::class, 'validateBatchCode'])
        ->middleware('throttle:video-code-validate');
    Route::get('/video-codes/my-redemptions', [VideoAccessCodeController::class, 'myRedemptions']);

    /*
    |--------------------------------------------------------------------------
    | Enrollment Requests
    |--------------------------------------------------------------------------
    */
    Route::post(
        '/centers/{center}/courses/{course}/enroll-request',
        [EnrollmentRequestController::class, 'store']
    );

    /*
    |--------------------------------------------------------------------------
    | Surveys
    |--------------------------------------------------------------------------
    */
    Route::get('/surveys/assigned', [SurveyController::class, 'assigned']);
    Route::get('/surveys/{survey}', [SurveyController::class, 'show']);
    Route::post('/surveys/{survey}/submit', [SurveyController::class, 'submit']);

    /*
    |--------------------------------------------------------------------------
    | Course Assets (Unified Read Surface)
    |--------------------------------------------------------------------------
    */
    Route::get('/centers/{center}/courses/{course}/assets', [CourseAssetController::class, 'index']);
    Route::get('/centers/{center}/assets/{type}/{id}', [CourseAssetController::class, 'show']);
    Route::post('/centers/{center}/assets/{type}/{id}/progress', [CourseAssetController::class, 'trackProgress']);

    /*
    |--------------------------------------------------------------------------
    | Quizzes
    |--------------------------------------------------------------------------
    */
    Route::post('/centers/{center}/assets/quiz/{quiz}/attempts', [QuizAttemptController::class, 'start']);
    Route::get('/centers/{center}/assets/quiz/attempts/{attempt}', [QuizAttemptController::class, 'show']);
    Route::post('/centers/{center}/assets/quiz/attempts/{attempt}/answers', [QuizAttemptController::class, 'answer']);
    Route::post('/centers/{center}/assets/quiz/attempts/{attempt}/submit', [QuizAttemptController::class, 'submit']);
    Route::get('/centers/{center}/assets/quiz/attempts/{attempt}/results', [QuizAttemptController::class, 'results']);

    /*
    |--------------------------------------------------------------------------
    | Assignments
    |--------------------------------------------------------------------------
    */
    Route::post('/centers/{center}/assets/assignment/{assignment}/submission', [AssignmentSubmissionController::class, 'store']);
    Route::post('/centers/{center}/assets/assignment/submissions/{submission}/files', [AssignmentSubmissionController::class, 'uploadFile']);
    Route::delete('/centers/{center}/assets/assignment/submissions/{submission}/files/{file}', [AssignmentSubmissionController::class, 'removeFile']);
    Route::post('/centers/{center}/assets/assignment/submissions/{submission}/submit', [AssignmentSubmissionController::class, 'submit']);

    /*
    |--------------------------------------------------------------------------
    | Assignment Groups
    |--------------------------------------------------------------------------
    */
    Route::get('/centers/{center}/assets/assignment/{assignment}/groups', [AssignmentGroupController::class, 'index']);
    Route::post('/centers/{center}/assets/assignment/{assignment}/groups', [AssignmentGroupController::class, 'store']);
    Route::get('/centers/{center}/assets/assignment/groups/{group}', [AssignmentGroupController::class, 'show']);
    Route::post('/centers/{center}/assets/assignment/groups/{group}/join', [AssignmentGroupController::class, 'join']);
    Route::post('/centers/{center}/assets/assignment/groups/{group}/leave', [AssignmentGroupController::class, 'leave']);

    /*
    |--------------------------------------------------------------------------
    | Education Lookups
    |--------------------------------------------------------------------------
    */
    Route::get('/centers/{center}/education', [EducationLookupController::class, 'index'])->whereNumber('center');
    Route::get('/centers/{center}/grades', [GradeLookupController::class, 'index'])->whereNumber('center');
    Route::get('/centers/{center}/schools', [SchoolLookupController::class, 'index'])->whereNumber('center');
    Route::get('/centers/{center}/colleges', [CollegeLookupController::class, 'index'])->whereNumber('center');

});
