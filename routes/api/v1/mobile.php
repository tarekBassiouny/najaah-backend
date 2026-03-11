<?php

declare(strict_types=1);

use App\Http\Controllers\Mobile\AssignmentController;
use App\Http\Controllers\Mobile\AssignmentGroupController;
use App\Http\Controllers\Mobile\AssignmentSubmissionController;
use App\Http\Controllers\Mobile\AuthController;
use App\Http\Controllers\Mobile\CategoryController;
use App\Http\Controllers\Mobile\CentersController;
use App\Http\Controllers\Mobile\DeviceChangeRequestController;
use App\Http\Controllers\Mobile\EnrolledCoursesController;
use App\Http\Controllers\Mobile\EnrollmentRequestController;
use App\Http\Controllers\Mobile\ExploreController;
use App\Http\Controllers\Mobile\ExtraViewRequestController;
use App\Http\Controllers\Mobile\InstructorController;
use App\Http\Controllers\Mobile\LearningAssetController;
use App\Http\Controllers\Mobile\MeController;
use App\Http\Controllers\Mobile\PdfController;
use App\Http\Controllers\Mobile\PlaybackController;
use App\Http\Controllers\Mobile\QuizAttemptController;
use App\Http\Controllers\Mobile\QuizController;
use App\Http\Controllers\Mobile\SearchController;
use App\Http\Controllers\Mobile\SurveyController;
use App\Http\Controllers\Mobile\VideoAccessCodeController;
use App\Http\Controllers\Mobile\VideoAccessRequestController;
use App\Http\Controllers\Mobile\WeeklyActivityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth (Public)
|--------------------------------------------------------------------------
*/
Route::post('/auth/send-otp', [AuthController::class, 'send'])->middleware('throttle:otp-send');
Route::post('/auth/verify', [AuthController::class, 'verify'])->middleware('throttle:otp-verify');
Route::post('/auth/refresh', [AuthController::class, 'refresh']);

/*
|--------------------------------------------------------------------------
| Device Change (Public, requires OTP verification)
|--------------------------------------------------------------------------
*/
Route::post('/device-change/submit', [DeviceChangeRequestController::class, 'submitWithOtp'])
    ->middleware('throttle:otp-verify');

/*
|--------------------------------------------------------------------------
| Authenticated Mobile Routes
|--------------------------------------------------------------------------
*/
Route::middleware('jwt.mobile')->group(function (): void {

    Route::get('/auth/me', [MeController::class, 'profile']);
    Route::get('/auth/me/profile', [MeController::class, 'profileDetails']);
    Route::post('/auth/me', [MeController::class, 'updateProfile']);
    Route::patch('/auth/me/education', [MeController::class, 'updateEducation']);
    Route::post('/auth/logout', [MeController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */
    Route::post('/settings/device-change', [DeviceChangeRequestController::class, 'store']);

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
    Route::middleware('ensure.unbranded.student')->group(function (): void {
        Route::get('/centers', [CentersController::class, 'index']);
        Route::get('/centers/{center}', [CentersController::class, 'show']);
        Route::get('/centers/{center}/categories', [CategoryController::class, 'centerIndex']);
    });

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
    | Quizzes
    |--------------------------------------------------------------------------
    */
    Route::get('/centers/{center}/courses/{course}/quizzes', [QuizController::class, 'index']);
    Route::get('/centers/{center}/quizzes/{quiz}', [QuizController::class, 'show']);
    Route::get('/centers/{center}/quizzes/{quiz}/my-attempts', [QuizController::class, 'myAttempts']);
    Route::post('/centers/{center}/quizzes/{quiz}/start', [QuizAttemptController::class, 'start']);
    Route::get('/centers/{center}/quiz-attempts/{attempt}', [QuizAttemptController::class, 'show']);
    Route::post('/centers/{center}/quiz-attempts/{attempt}/answer', [QuizAttemptController::class, 'answer']);
    Route::post('/centers/{center}/quiz-attempts/{attempt}/submit', [QuizAttemptController::class, 'submit']);
    Route::get('/centers/{center}/quiz-attempts/{attempt}/results', [QuizAttemptController::class, 'results']);

    /*
    |--------------------------------------------------------------------------
    | Assignments
    |--------------------------------------------------------------------------
    */
    Route::get('/centers/{center}/courses/{course}/assignments', [AssignmentController::class, 'index']);
    Route::get('/centers/{center}/assignments/{assignment}', [AssignmentController::class, 'show']);
    Route::get('/centers/{center}/assignments/{assignment}/my-submission', [AssignmentController::class, 'mySubmission']);
    Route::post('/centers/{center}/assignments/{assignment}/submissions', [AssignmentSubmissionController::class, 'store']);
    Route::post('/centers/{center}/submissions/{submission}/files', [AssignmentSubmissionController::class, 'uploadFile']);
    Route::delete('/centers/{center}/submissions/{submission}/files/{file}', [AssignmentSubmissionController::class, 'removeFile']);
    Route::post('/centers/{center}/submissions/{submission}/submit', [AssignmentSubmissionController::class, 'submit']);

    /*
    |--------------------------------------------------------------------------
    | Assignment Groups
    |--------------------------------------------------------------------------
    */
    Route::get('/centers/{center}/assignments/{assignment}/groups', [AssignmentGroupController::class, 'index']);
    Route::post('/centers/{center}/assignments/{assignment}/groups', [AssignmentGroupController::class, 'store']);
    Route::get('/centers/{center}/assignment-groups/{group}', [AssignmentGroupController::class, 'show']);
    Route::post('/centers/{center}/assignment-groups/{group}/join', [AssignmentGroupController::class, 'join']);
    Route::post('/centers/{center}/assignment-groups/{group}/leave', [AssignmentGroupController::class, 'leave']);

    /*
    |--------------------------------------------------------------------------
    | Learning Assets (Published Only)
    |--------------------------------------------------------------------------
    */
    Route::get('/centers/{center}/courses/{course}/summaries', [LearningAssetController::class, 'summaries']);
    Route::get('/centers/{center}/summaries/{asset}', [LearningAssetController::class, 'summary']);
    Route::get('/centers/{center}/courses/{course}/flashcards', [LearningAssetController::class, 'flashcards']);
    Route::get('/centers/{center}/flashcards/{asset}', [LearningAssetController::class, 'flashcardSet']);
    Route::get('/centers/{center}/courses/{course}/interactive-activities', [LearningAssetController::class, 'interactiveActivities']);
    Route::get('/centers/{center}/interactive-activities/{asset}', [LearningAssetController::class, 'interactiveActivity']);

});
