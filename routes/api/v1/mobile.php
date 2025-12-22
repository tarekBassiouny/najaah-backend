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
use App\Http\Controllers\Mobile\SearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth (Public)
|--------------------------------------------------------------------------
*/
Route::post('/auth/send-otp', [AuthController::class, 'send']);
Route::post('/auth/verify', [AuthController::class, 'verify']);
Route::post('/auth/refresh', [AuthController::class, 'refresh']);

/*
|--------------------------------------------------------------------------
| Authenticated Mobile Routes
|--------------------------------------------------------------------------
*/
Route::middleware('jwt.mobile')->group(function (): void {

    Route::get('/auth/me', [MeController::class, 'profile']);
    Route::post('/auth/me', [MeController::class, 'updateProfile']);
    Route::post('/auth/logout', [MeController::class, 'logout']);

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
    | Device Change Requests
    |--------------------------------------------------------------------------
    */
    Route::post('/settings/device-change', [DeviceChangeRequestController::class, 'store']);

    /*
    |--------------------------------------------------------------------------
    | Enrollment Requests
    |--------------------------------------------------------------------------
    */
    Route::post(
        '/centers/{center}/courses/{course}/enroll-request',
        [EnrollmentRequestController::class, 'store']
    );

});
