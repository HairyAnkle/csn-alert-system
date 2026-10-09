<?php

use App\Http\Controllers\ChildProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmergencyController;
use App\Http\Controllers\IncidentReportController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public signed invitation acceptance routes
Route::get('/invitation/{user}', [InvitationController::class, 'show'])->name('invitation.accept');
Route::post('/invitation/{user}', [InvitationController::class, 'store'])->name('invitation.store');

Route::middleware(['auth', 'prevent_back_history'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', fn () => redirect()->route('dashboard'));

    Route::get('/emergencies', [EmergencyController::class, 'index'])->name('emergencies.index');
    Route::post('/emergencies', [EmergencyController::class, 'store'])->name('emergencies.store');
    Route::patch('/emergencies/{emergency}/acknowledge', [EmergencyController::class, 'acknowledge'])->name('emergencies.acknowledge');
    Route::patch('/emergencies/{emergency}/dispatch', [EmergencyController::class, 'dispatch'])->name('emergencies.dispatch');
    Route::patch('/emergencies/{emergency}/resolve', [EmergencyController::class, 'resolve'])->name('emergencies.resolve');

    Route::resource('children', ChildProfileController::class)->except(['create', 'edit']);

    Route::resource('users', UserController::class)->except(['create', 'edit']);
    Route::post('/users/{user}/invite', [UserController::class, 'invite'])->name('users.invite');

    Route::get('/incident-reports', [IncidentReportController::class, 'index'])->name('incidents.index');
    Route::get('/incident-reports/export-pdf', [IncidentReportController::class, 'exportPdf'])->name('incidents.export.pdf');
    Route::get('/incident-reports/export-csv', [IncidentReportController::class, 'exportCsv'])->name('incidents.export.csv');
    Route::patch('/incident-reports/{emergency}/summary', [IncidentReportController::class, 'updateSummary'])->name('incidents.summary');

    Route::get('/settings', [SettingController::class, 'show'])->name('settings.show');
    Route::patch('/settings', [SettingController::class, 'update'])->name('settings.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
