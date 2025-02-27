<?php

use App\Http\Controllers\Application\Auth\LoginController;
use App\Http\Controllers\Application\Auth\RegisterController;
use App\Http\Controllers\Application\Auth\VerifyController;
use App\Http\Controllers\Crm\Authenticated\ServiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('app')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::post('/auth/register', [RegisterController::class, 'register'])
            ->name('app.register');
        Route::post('/auth/login', [LoginController::class, 'login'])
            ->name('app.login');
        Route::post('/auth/check-credentials', [LoginController::class, 'checkCredentials'])
            ->name('app.check-credentials');
        Route::post('/auth/verify-otp', [VerifyController::class, 'verifyOTP'])
            ->name('app.verify-otp');
        Route::post('/auth/send-otp', [VerifyController::class, 'sendOTP'])
            ->name('app.send-otp');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [LoginController::class, 'destroy'])
            ->name('app.logout');
        Route::get('/app/services', [ServiceController::class, 'app_index'])
            ->name('app.services');
    });
});
