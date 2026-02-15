<?php

declare(strict_types=1);

use App\Http\Controllers\Mobile\AuthController;
use App\Http\Controllers\Mobile\CategoryController;
use App\Http\Controllers\Mobile\CentersController;
use App\Http\Controllers\Mobile\DeviceChangeRequestController;
use App\Http\Controllers\Mobile\EnrolledCoursesController;
use App\Http\Controllers\Mobile\EnrollmentRequestController;
use App\Http\Controllers\Mobile\ExploreController;
use App\Http\Controllers\Mobile\ExtraViewRequestController;
use App\Http\Controllers\Mobile\InstructorController;
use App\Http\Controllers\Mobile\MeController;
use App\Http\Controllers\Mobile\PdfController;
use App\Http\Controllers\Mobile\PlaybackController;
use App\Http\Controllers\Mobile\SearchController;
use App\Http\Controllers\Mobile\SurveyController;
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

});
