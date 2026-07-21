<?php

use App\Http\Controllers\Api\V1\Admin\ReportExportController;
use App\Http\Controllers\Web\Admin\AuditLogController;
use App\Http\Controllers\Web\Admin\AuthController;
use App\Http\Controllers\Web\Admin\BookingController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\DriverVerificationController;
use App\Http\Controllers\Web\Admin\PaymentController;
use App\Http\Controllers\Web\Admin\ReportController;
use App\Http\Controllers\Web\Admin\TourPackageController;
use App\Http\Controllers\Web\Admin\VehicleController;
use App\Http\Controllers\Web\Admin\WithdrawalController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AuthController::class, 'create'])->name('login');
        Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth', 'admin.web'])->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::resource('tour-packages', TourPackageController::class)
            ->except('show');

        Route::resource('vehicles', VehicleController::class)
            ->except('show');

        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
        Route::patch('/bookings/{booking}/status', [BookingController::class, 'updateStatus'])->name('bookings.status');
        Route::post('/bookings/{booking}/assignments', [BookingController::class, 'assign'])->name('bookings.assignments.store');
        Route::patch('/bookings/{booking}/assignments/{assignment}/cancel', [BookingController::class, 'cancelAssignment'])->name('bookings.assignments.cancel');
        Route::put('/bookings/{booking}/participant-allocations', [BookingController::class, 'allocateParticipant'])->name('bookings.allocations.update');

        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        Route::patch('/payments/{payment}', [PaymentController::class, 'update'])->name('payments.update');

        Route::get('/drivers', [DriverVerificationController::class, 'index'])->name('drivers.index');
        Route::get('/drivers/{driverProfile}', [DriverVerificationController::class, 'show'])->name('drivers.show');
        Route::patch('/drivers/{driverProfile}', [DriverVerificationController::class, 'updateDriver'])->name('drivers.update');
        Route::patch('/drivers/{driverProfile}/vehicles/{vehicle}', [DriverVerificationController::class, 'updateVehicle'])->name('drivers.vehicles.update');

        Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
        Route::get('/withdrawals/{withdrawal}', [WithdrawalController::class, 'show'])->name('withdrawals.show');
        Route::patch('/withdrawals/{withdrawal}', [WithdrawalController::class, 'update'])->name('withdrawals.update');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export/bookings', [ReportExportController::class, 'bookings'])->name('reports.bookings');
        Route::get('/reports/export/payments', [ReportExportController::class, 'payments'])->name('reports.payments');
        Route::get('/reports/export/drivers', [ReportExportController::class, 'drivers'])->name('reports.drivers');
        Route::get('/reports/export/withdrawals', [ReportExportController::class, 'withdrawals'])->name('reports.withdrawals');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');

        Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    });
});
