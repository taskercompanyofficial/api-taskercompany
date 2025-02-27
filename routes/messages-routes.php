<?php

use App\Http\Controllers\Messages\ChatRoomsController;
use Illuminate\Support\Facades\Route;

// Auth Routes
Route::prefix('messages')->group(function () {
    Route::middleware('guest')->group(function () {});

    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('/chat-rooms', ChatRoomsController::class);
    });
});
