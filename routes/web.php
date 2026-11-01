<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AppointmentArchiveController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AppointmentConversationController;
use App\Http\Controllers\AppointmentChangeController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\PriestCalendarController;
use App\Http\Controllers\PriestProfileController;
use App\Http\Controllers\UserNotificationController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AgendaController::class, 'index'])->name('home');
Route::get('/language/{locale}', [LocaleController::class, 'update'])->where('locale', 'en|el|ru')->name('language.update');
Route::get('/p/{priest:slug}', [AgendaController::class, 'show'])->name('priests.show');
Route::get('/p/{priest:slug}/available-dates', [AgendaController::class, 'availableDates'])->name('priests.available-dates');
Route::get('/p/{priest:slug}/availability', [AgendaController::class, 'availability'])->name('priests.availability');
Route::post('/p/{priest:slug}/appointments', [AgendaController::class, 'store'])->middleware('auth')->name('appointments.store');

Route::get('/sign-in', [OtpController::class, 'requestForm'])->name('login');
Route::get('/sign-in/email-availability', [OtpController::class, 'emailAvailability'])->middleware('throttle:30,1')->name('otp.email-availability');
Route::post('/sign-in', [OtpController::class, 'send'])->middleware('throttle:10,1')->name('otp.send');
Route::get('/verify-code', [OtpController::class, 'verifyForm'])->name('otp.form');
Route::post('/verify-code', [OtpController::class, 'verify'])->middleware('throttle:10,1')->name('otp.verify');
Route::post('/sign-out', [OtpController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/calendar', [PriestCalendarController::class, 'index'])->name('calendar');
    Route::get('/notifications', [UserNotificationController::class, 'index'])->name('notifications.index');
    Route::get('/archive', [AppointmentArchiveController::class, 'index'])->name('archive');
    Route::get('/privacy/export', [PrivacyController::class, 'export'])->name('privacy.export');
    Route::post('/notifications/{notificationId}/archive', [AppointmentArchiveController::class, 'archiveNotification'])->name('notifications.archive');
    Route::post('/notifications/read-all', [UserNotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notificationId}/read', [UserNotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/admin/priests', [AdminController::class, 'storePriest'])->name('admin.priests.store');
    Route::post('/admin/priests/{priest}/verification', [AdminController::class, 'updateVerification'])->name('admin.priests.verification');
    Route::post('/admin/priests/{priest}/approval', [AdminController::class, 'updatePriestApproval'])->name('admin.priests.approval');
    Route::post('/appointments/{appointment}/respond', [DashboardController::class, 'respond'])->name('appointments.respond');
    Route::get('/appointments/{appointment}/messages', [AppointmentConversationController::class, 'show'])->name('appointments.messages');
    Route::get('/appointments/{appointment}/messages/updates', [AppointmentConversationController::class, 'updates'])->name('appointments.messages.updates');
    Route::post('/appointments/{appointment}/change-proposal', [AppointmentChangeController::class, 'propose'])->name('appointments.change.propose');
    Route::post('/appointments/{appointment}/change-response', [AppointmentChangeController::class, 'respond'])->name('appointments.change.respond');
    Route::post('/appointments/{appointment}/messages', [AppointmentConversationController::class, 'store'])->name('appointments.messages.store');
    Route::get('/profile', [PriestProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [PriestProfileController::class, 'update'])->name('profile.update');
    Route::post('/availability', [PriestProfileController::class, 'storeAvailability'])->name('availability.store');
    Route::put('/availability/{availability}', [PriestProfileController::class, 'updateAvailability'])->name('availability.update');
    Route::delete('/availability/{availability}', [PriestProfileController::class, 'deleteAvailability'])->name('availability.delete');
    Route::post('/blocked-dates', [PriestProfileController::class, 'storeBlockedDate'])->name('blocked.store');
    Route::put('/blocked-dates/{blockedDate}', [PriestProfileController::class, 'updateBlockedDate'])->name('blocked.update');
    Route::delete('/blocked-dates/{blockedDate}', [PriestProfileController::class, 'deleteBlockedDate'])->name('blocked.delete');
});
