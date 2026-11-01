<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PriestController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\CalendarController;
use App\Http\Controllers\Api\V1\AppointmentMessageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/admin-login', [AuthController::class, 'adminLogin'])->middleware('throttle:10,1')->name('auth.admin-login');
    Route::post('/auth/code', [AuthController::class, 'sendCode'])->middleware('throttle:5,1')->name('auth.code');
    Route::post('/auth/verify', [AuthController::class, 'verify'])->middleware('throttle:10,1')->name('auth.verify');
    Route::get('/priests', [PriestController::class, 'index'])->name('priests.index');
    Route::get('/priests/{priest:slug}', [PriestController::class, 'show'])->name('priests.show');
    Route::get('/priests/{priest:slug}/availability', [PriestController::class, 'availability'])->name('priests.availability');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::delete('/auth/token', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/admin/priests', [AdminController::class, 'storePriest'])->name('admin.priests.store');
        Route::post('/priests/{priest:slug}/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
        Route::get('/appointments', [AppointmentController::class, 'mine'])->name('appointments.mine');
        Route::get('/appointments/{appointment}/messages', [AppointmentMessageController::class, 'index'])->name('appointments.messages.index');
        Route::post('/appointments/{appointment}/messages', [AppointmentMessageController::class, 'store'])->name('appointments.messages.store');
        Route::get('/priest/appointments', [AppointmentController::class, 'priestIndex'])->name('appointments.priest');
        Route::get('/priest/calendar', [CalendarController::class, 'index'])->name('calendar.priest');
        Route::patch('/priest/appointments/{appointment}/response', [AppointmentController::class, 'respond'])->name('appointments.respond');
        Route::put('/priest/profile', [PriestController::class, 'update'])->name('profile.update');
        Route::post('/priest/availability', [PriestController::class, 'storeAvailability'])->name('availability.store');
        Route::delete('/priest/availability/{availability}', [PriestController::class, 'deleteAvailability'])->name('availability.delete');
        Route::post('/priest/blocked-dates', [PriestController::class, 'storeBlockedDate'])->name('blocked-dates.store');
        Route::delete('/priest/blocked-dates/{blockedDate}', [PriestController::class, 'deleteBlockedDate'])->name('blocked-dates.delete');
    });
});
