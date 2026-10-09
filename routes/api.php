<?php

use App\Http\Controllers\Api\AnnouncementApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChildApiController;
use App\Http\Controllers\Api\EmergencyApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Guardian API Routes
|--------------------------------------------------------------------------
|
| Handlers for Parañaque CSN Guardian Mobile App.
| Bearer token authentication via Laravel Sanctum.
|
*/

// Public authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
});

// Protected routes (Sanctum Bearer token)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/child', [ChildApiController::class, 'update']);

    Route::get('/emergencies', [EmergencyApiController::class, 'index']);
    Route::post('/emergencies', [EmergencyApiController::class, 'store']);
    Route::get('/emergencies/{emergency}', [EmergencyApiController::class, 'show']);
    Route::patch('/emergencies/{emergency}/location', [EmergencyApiController::class, 'updateLocation']);
    Route::post('/emergencies/{emergency}/cancel', [EmergencyApiController::class, 'cancel']);

    Route::get('/announcements', [AnnouncementApiController::class, 'index']);
});
