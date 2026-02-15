<?php

use App\Http\Controllers\Admin\SurveyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:survey.manage', 'scope.system_admin'])->group(function (): void {
    Route::get('/surveys/target-students', [SurveyController::class, 'systemTargetStudents']);
    Route::get('/surveys', [SurveyController::class, 'systemIndex']);
    Route::post('/surveys', [SurveyController::class, 'systemStore']);
    Route::get('/surveys/{survey}', [SurveyController::class, 'systemShow']);
    Route::put('/surveys/{survey}', [SurveyController::class, 'systemUpdate']);
    Route::delete('/surveys/{survey}', [SurveyController::class, 'systemDestroy']);
    Route::post('/surveys/{survey}/assign', [SurveyController::class, 'systemAssign']);
    Route::post('/surveys/{survey}/close', [SurveyController::class, 'systemClose']);
    Route::get('/surveys/{survey}/analytics', [SurveyController::class, 'systemAnalytics']);
});

Route::middleware(['require.permission:survey.manage', 'scope.center_route'])->group(function (): void {
    Route::get('/centers/{center}/surveys/target-students', [SurveyController::class, 'centerTargetStudents'])->whereNumber('center');
    Route::get('/centers/{center}/surveys', [SurveyController::class, 'centerIndex'])->whereNumber('center');
    Route::post('/centers/{center}/surveys', [SurveyController::class, 'centerStore'])->whereNumber('center');
    Route::get('/centers/{center}/surveys/{survey}', [SurveyController::class, 'centerShow'])->whereNumber('center');
    Route::put('/centers/{center}/surveys/{survey}', [SurveyController::class, 'centerUpdate'])->whereNumber('center');
    Route::delete('/centers/{center}/surveys/{survey}', [SurveyController::class, 'centerDestroy'])->whereNumber('center');
    Route::post('/centers/{center}/surveys/{survey}/assign', [SurveyController::class, 'centerAssign'])->whereNumber('center');
    Route::post('/centers/{center}/surveys/{survey}/close', [SurveyController::class, 'centerClose'])->whereNumber('center');
    Route::get('/centers/{center}/surveys/{survey}/analytics', [SurveyController::class, 'centerAnalytics'])->whereNumber('center');
});
