<?php

use App\Http\Controllers\Api\V1\AlertController;
use App\Http\Controllers\Api\V1\ApplicationController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CvController;
use App\Http\Controllers\Api\V1\JobController;
use App\Http\Controllers\Api\V1\LeadController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\CvDownloadController;
use Illuminate\Support\Facades\Route;

/*
| TalentHub API, version 1. Base path: /api/v1
| Authenticate with "Authorization: Bearer <token>" (token from register or login).
*/
Route::prefix('v1')->name('api.')->group(function () {

    // Public
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:forms')->name('register');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login');
    Route::post('leads', [LeadController::class, 'store'])->middleware('throttle:forms')->name('leads.store');

    Route::middleware('throttle:api')->group(function () {
        Route::get('jobs', [JobController::class, 'index'])->name('jobs.index');
        Route::get('jobs/{job}', [JobController::class, 'show'])->whereNumber('job')->name('jobs.show');
    });

    // Signed in (any role)
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::get('cvs/{cv}/download', CvDownloadController::class)->whereNumber('cv')->name('cvs.download');

        // Candidates only
        Route::middleware('role:candidate')->group(function () {
            Route::put('me/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::get('me/matches', [ProfileController::class, 'matches'])->name('matches');

            Route::post('jobs/{job}/apply', [JobController::class, 'apply'])->whereNumber('job')->name('jobs.apply');

            Route::get('applications', [ApplicationController::class, 'index'])->name('applications.index');
            Route::get('applications/{application}', [ApplicationController::class, 'show'])->whereNumber('application')->name('applications.show');
            Route::post('applications/{application}/withdraw', [ApplicationController::class, 'withdraw'])->whereNumber('application')->name('applications.withdraw');

            Route::get('cvs', [CvController::class, 'index'])->name('cvs.index');
            Route::post('cvs', [CvController::class, 'store'])->name('cvs.store');
            Route::delete('cvs/{cv}', [CvController::class, 'destroy'])->whereNumber('cv')->name('cvs.destroy');

            Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');
            Route::post('alerts', [AlertController::class, 'store'])->name('alerts.store');
            Route::delete('alerts/{alert}', [AlertController::class, 'destroy'])->whereNumber('alert')->name('alerts.destroy');
        });
    });
});
