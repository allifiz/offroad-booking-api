<?php

use App\Http\Controllers\Api\V1\Admin\ReportExportController;
use App\Http\Controllers\Web\Admin\AuditLogController;
use App\Http\Controllers\Web\Admin\AuthController;
use App\Http\Controllers\Web\Admin\BookingController;
use App\Http\Controllers\Web\Admin\CustomerController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\DriverVerificationController;
use App\Http\Controllers\Web\Admin\PaymentController;
use App\Http\Controllers\Web\Admin\ReportController;
use App\Http\Controllers\Web\Admin\TourPackageController;
use App\Http\Controllers\Web\Admin\TravelGroupController;
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

        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::patch('/customers/{customer}/status', [CustomerController::class, 'updateStatus'])->name('customers.status');

        Route::resource('tour-packages', TourPackageController::class)
            ->except('show');

        Route::resource('vehicles', VehicleController::class)
            ->except('show');

        Route::get('/travel-groups', [TravelGroupController::class, 'index'])->name('travel-groups.index');
        Route::get('/travel-groups/create', [TravelGroupController::class, 'create'])->name('travel-groups.create');
        Route::post('/travel-groups', [TravelGroupController::class, 'store'])->name('travel-groups.store');
        Route::get('/travel-groups/{travelGroup}', [TravelGroupController::class, 'show'])->name('travel-groups.show');
        Route::patch('/travel-groups/{travelGroup}/status', [TravelGroupController::class, 'updateStatus'])->name('travel-groups.status');
        Route::post('/travel-groups/{travelGroup}/bookings', [TravelGroupController::class, 'attachBooking'])->name('travel-groups.bookings.store');

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
