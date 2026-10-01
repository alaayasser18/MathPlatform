<?php

use App\Http\Controllers\Admin\AdminActivationController;
use App\Http\Controllers\Admin\AdminDeviceController;
use App\Http\Controllers\Admin\AdminExamController;
use App\Http\Controllers\Admin\AdminLessonController;
use App\Http\Controllers\Admin\AdminStudentController;
use App\Http\Controllers\Admin\AdminSubscriptionCodeController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Student\StudentExamController;
use App\Http\Controllers\Student\StudentLessonController;
use App\Http\Controllers\Student\StudentProfileController;
use App\Http\Controllers\Student\StudentVideoController;
use App\Http\Middleware\AdminOnly;
use App\Http\Middleware\StudentOnly;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Student Authentication
    |--------------------------------------------------------------------------
    */

    Route::prefix('auth')->group(function () {

        Route::post('/activate', [AuthController::class, 'activate']);

        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware([
            'auth:sanctum',
            StudentOnly::class,
            'trusted.device',
        ])->group(function () {

            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });


    /*
    |--------------------------------------------------------------------------
    | Admin Authentication
    |--------------------------------------------------------------------------
    */

    Route::prefix('admin/auth')->group(function () {

    Route::post('/login', [AdminAuthController::class, 'login']);

    Route::middleware([
        'auth:sanctum',
        AdminOnly::class,
    ])->group(function () {

        Route::post('/logout', [AdminAuthController::class, 'logout']);
    });
});


    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware([
        'auth:sanctum',
        AdminOnly::class,
    ])
        ->prefix('admin')
        ->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Activation Requests
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/activation-requests',
                [AdminActivationController::class, 'index']
            );

            Route::post(
                '/activation-requests/{subscription}/approve',
                [AdminActivationController::class, 'approve']
            );

            Route::post(
                '/activation-requests/{subscription}/cancel',
                [AdminActivationController::class, 'cancel']
            );

            Route::get(
                '/approved-students',
                [AdminActivationController::class, 'approvedStudents']
            );


            /*
            |--------------------------------------------------------------------------
            | Subscriptions
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/subscriptions/{subscription}/deactivate',
                [AdminActivationController::class, 'deactivateSubscription']
            );


            /*
            |--------------------------------------------------------------------------
            | Devices
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/subscriptions/{subscription}/reset-device',
                [AdminDeviceController::class, 'reset']
            );


            /*
            |--------------------------------------------------------------------------
            | Students
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/students',
                [AdminStudentController::class, 'index']
            );

            Route::get(
                '/students/{student}',
                [AdminStudentController::class, 'show']
            );

            Route::post(
                '/students',
                [AdminStudentController::class, 'store']
            );

            Route::put(
                '/students/{student}',
                [AdminStudentController::class, 'update']
            );


            /*
            |--------------------------------------------------------------------------
            | Subscription Codes
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/subscription-codes',
                [AdminSubscriptionCodeController::class, 'index']
            );


            /*
            |--------------------------------------------------------------------------
            | Lessons
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/lessons',
                [AdminLessonController::class, 'index']
            );

            Route::post(
                '/lessons',
                [AdminLessonController::class, 'store']
            );

            Route::delete(
                '/lessons/{lesson}',
                [AdminLessonController::class, 'destroy']
            );


            /*
            |--------------------------------------------------------------------------
            | Exams
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/exams',
                [AdminExamController::class, 'index']
            );

            Route::post(
                '/exams',
                [AdminExamController::class, 'store']
            );

            Route::get(
                '/exams/{exam}',
                [AdminExamController::class, 'show']
            );

            Route::put(
                '/exams/{exam}',
                [AdminExamController::class, 'update']
            );

            Route::delete(
                '/exams/{exam}',
                [AdminExamController::class, 'destroy']
            );


            /*
            |--------------------------------------------------------------------------
            | Questions
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/exams/{exam}/questions',
                [AdminExamController::class, 'storeQuestion']
            );

            Route::put(
                '/questions/{question}',
                [AdminExamController::class, 'updateQuestion']
            );

            Route::delete(
                '/questions/{question}',
                [AdminExamController::class, 'destroyQuestion']
            );


            /*
            |--------------------------------------------------------------------------
            | Options
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/questions/{question}/options',
                [AdminExamController::class, 'storeOption']
            );

            Route::put(
                '/options/{option}',
                [AdminExamController::class, 'updateOption']
            );

            Route::delete(
                '/options/{option}',
                [AdminExamController::class, 'destroyOption']
            );
        });


    /*
    |--------------------------------------------------------------------------
    | Student Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware([
        'auth:sanctum',
        StudentOnly::class,
        'trusted.device',
    ])
        ->prefix('student')
        ->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Profile
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/profile',
                [StudentProfileController::class, 'show']
            );


            /*
            |--------------------------------------------------------------------------
            | Lessons
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/lessons',
                [StudentLessonController::class, 'index']
            );


            /*
            |--------------------------------------------------------------------------
            | Videos
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/videos/{video}',
                [StudentVideoController::class, 'show']
            );

            Route::post(
                '/videos/{video}/progress',
                [StudentVideoController::class, 'updateProgress']
            );


            /*
            |--------------------------------------------------------------------------
            | Exams
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/exams/{exam}',
                [StudentExamController::class, 'show']
            );

            Route::post(
                '/exams/{exam}/start',
                [StudentExamController::class, 'start']
            );

            Route::post(
                '/exams/{exam}/submit',
                [StudentExamController::class, 'submit']
            );
        });
});