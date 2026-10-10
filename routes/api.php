<?php

use App\Http\Controllers\Api\{AuthController, ChildController, EmergencyController, AnnouncementController};
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Guardian API Routes (Section 7.2)
|--------------------------------------------------------------------------
|
| Handlers for Parañaque CSN Guardian Mobile App.
| Bearer token authentication via Laravel Sanctum & role:guardian check.
|
*/

Route::prefix('auth')->group(function () {
    Route::post('login',           [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('register',        [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('google',          [AuthController::class, 'google']);   // later
});

Route::middleware(['auth:sanctum', 'role:guardian'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('me',           [AuthController::class, 'me']);

    Route::put('child', [ChildController::class, 'update']);

    Route::get('emergencies',                      [EmergencyController::class, 'index']);
    Route::post('emergencies',                     [EmergencyController::class, 'store'])->middleware('throttle:6,1');
    Route::get('emergencies/{emergency}',          [EmergencyController::class, 'show']);
    Route::patch('emergencies/{emergency}/location', [EmergencyController::class, 'location'])->middleware('throttle:60,1');
    Route::post('emergencies/{emergency}/cancel',  [EmergencyController::class, 'cancel']);

    Route::get('announcements', [AnnouncementController::class, 'index']);
});
