<?php

use App\Http\Controllers\Web\Admin\AuthController;
use App\Http\Controllers\Web\Admin\BookingController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\PaymentController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AuthController::class, 'create'])->name('login');
        Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth', 'admin.web'])->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
        Route::patch('/bookings/{booking}/status', [BookingController::class, 'updateStatus'])->name('bookings.status');
        Route::post('/bookings/{booking}/assignments', [BookingController::class, 'assign'])->name('bookings.assignments.store');
        Route::patch('/bookings/{booking}/assignments/{assignment}/cancel', [BookingController::class, 'cancelAssignment'])->name('bookings.assignments.cancel');
        Route::put('/bookings/{booking}/participant-allocations', [BookingController::class, 'allocateParticipant'])->name('bookings.allocations.update');

        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        Route::patch('/payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');
        Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    });
});
