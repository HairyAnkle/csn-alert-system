<?php

use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChildController;
use App\Http\Controllers\Api\EmergencyController;
use App\Http\Controllers\Api\SupportChatApiController;
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
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('google', [AuthController::class, 'google']);   // later
    Route::post('firebase', [AuthController::class, 'firebase'])->middleware('throttle:10,1');
    Route::post('phone', [AuthController::class, 'phone'])->middleware('throttle:10,1');
    Route::post('phone/send-otp', [AuthController::class, 'sendOtp'])->middleware('throttle:10,1');
    Route::post('phone/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1');
});

Route::middleware(['auth:sanctum', 'role:guardian'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    Route::put('child', [ChildController::class, 'update']);

    Route::get('emergencies', [EmergencyController::class, 'index']);
    Route::post('emergencies', [EmergencyController::class, 'store'])->middleware('throttle:6,1');
    Route::get('emergencies/{emergency}', [EmergencyController::class, 'show']);
    Route::patch('emergencies/{emergency}/location', [EmergencyController::class, 'location'])->middleware('throttle:60,1');
    Route::post('emergencies/{emergency}/cancel', [EmergencyController::class, 'cancel']);

    Route::get('announcements', [AnnouncementController::class, 'index']);

    Route::get('support/messages', [SupportChatApiController::class, 'index']);
    Route::post('support/messages', [SupportChatApiController::class, 'store']);
});
