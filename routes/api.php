<?php

use App\Http\Controllers\Api\V1\Admin\AuditLogController;
use App\Http\Controllers\Api\V1\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\DocumentVerificationController;
use App\Http\Controllers\Api\V1\Admin\DriverAssignmentController;
use App\Http\Controllers\Api\V1\Admin\DriverVehicleVerificationController;
use App\Http\Controllers\Api\V1\Admin\DriverVerificationController;
use App\Http\Controllers\Api\V1\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Api\V1\Admin\ReportExportController;
use App\Http\Controllers\Api\V1\Admin\TourPackageController as AdminTourPackageController;
use App\Http\Controllers\Api\V1\Admin\TravelGroupController;
use App\Http\Controllers\Api\V1\Admin\VehicleController as AdminVehicleController;
use App\Http\Controllers\Api\V1\Admin\WithdrawalController as AdminWithdrawalController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\CustomerProfileController;
use App\Http\Controllers\Api\V1\CustomerRegistrationController;
use App\Http\Controllers\Api\V1\DriverAssignmentController as DriverAssignmentResponseController;
use App\Http\Controllers\Api\V1\DriverDeviceTokenController;
use App\Http\Controllers\Api\V1\DriverDashboardController;
use App\Http\Controllers\Api\V1\DriverDocumentController;
use App\Http\Controllers\Api\V1\DriverPointController;
use App\Http\Controllers\Api\V1\DriverRegistrationController;
use App\Http\Controllers\Api\V1\DriverVehicleMediaController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\TourPackageController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', function (): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => 'Offroad Booking API is running.',
            'timestamp' => now()->toISOString(),
        ]);
    })->middleware('throttle:authenticated-read');

    Route::post('/driver/register', [DriverRegistrationController::class, 'store'])
        ->middleware('throttle:public-registration');
    Route::post('/customers/register', [CustomerRegistrationController::class, 'store'])
        ->middleware('throttle:public-registration');

    Route::get('/tour-packages', [TourPackageController::class, 'index'])
        ->middleware('throttle:authenticated-read');
    Route::get('/tour-packages/{tourPackage}', [TourPackageController::class, 'show'])
        ->middleware('throttle:authenticated-read');

    Route::prefix('auth')->group(function (): void {
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:auth-login');

        Route::middleware(['auth:sanctum', 'throttle:authenticated-read'])->group(function (): void {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::middleware(['auth:sanctum', 'throttle:authenticated-read'])->group(function (): void {
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    });

    Route::prefix('customer')
        ->middleware(['auth:sanctum', 'role:customer'])
        ->group(function (): void {
            Route::get('/profile', [CustomerProfileController::class, 'show'])->middleware('throttle:authenticated-read');
            Route::patch('/profile', [CustomerProfileController::class, 'update'])->middleware('throttle:customer-write');
            Route::get('/bookings', [BookingController::class, 'index'])->middleware('throttle:authenticated-read');
            Route::post('/bookings', [BookingController::class, 'store'])->middleware('throttle:customer-write');
            Route::get('/bookings/{booking}', [BookingController::class, 'show'])->middleware('throttle:authenticated-read');
            Route::get('/payments', [PaymentController::class, 'index'])->middleware('throttle:authenticated-read');
            Route::post('/bookings/{booking}/payments', [PaymentController::class, 'store'])->middleware('throttle:file-upload');
            Route::get('/payments/{payment}', [PaymentController::class, 'show'])->middleware('throttle:authenticated-read');
        });

    Route::prefix('driver')
        ->middleware(['auth:sanctum', 'role:driver'])
        ->group(function (): void {
            Route::get('/profile', [DriverDashboardController::class, 'showProfile'])->middleware('throttle:authenticated-read');
            Route::patch('/profile', [DriverDashboardController::class, 'updateProfile'])->middleware('throttle:driver-write');
            Route::patch('/availability', [DriverDashboardController::class, 'updateAvailability'])->middleware('throttle:driver-write');
            Route::get('/vehicles', [DriverDashboardController::class, 'vehicles'])->middleware('throttle:authenticated-read');
            Route::post('/vehicles', [DriverDashboardController::class, 'storeVehicle'])->middleware('throttle:driver-write');
            Route::get('/vehicles/{vehicle}', [DriverDashboardController::class, 'showVehicle'])->middleware('throttle:authenticated-read');
            Route::patch('/vehicles/{vehicle}', [DriverDashboardController::class, 'updateVehicle'])->middleware('throttle:driver-write');
            Route::delete('/vehicles/{vehicle}', [DriverDashboardController::class, 'destroyVehicle'])->middleware('throttle:driver-write');
            Route::post('/documents/{driverDocument}/reupload', [DriverDocumentController::class, 'reuploadDriverDocument'])->middleware('throttle:file-upload');
            Route::post('/vehicles/{vehicle}/documents', [DriverVehicleMediaController::class, 'storeDocument'])->middleware('throttle:file-upload');
            Route::post('/vehicles/{vehicle}/documents/{vehicleDocument}/reupload', [DriverDocumentController::class, 'reuploadVehicleDocument'])->middleware('throttle:file-upload');
            Route::post('/vehicles/{vehicle}/photos', [DriverVehicleMediaController::class, 'storePhoto'])->middleware('throttle:file-upload');
            Route::put('/vehicles/{vehicle}/photos/order', [DriverVehicleMediaController::class, 'reorderPhotos'])->middleware('throttle:driver-write');
            Route::delete('/vehicles/{vehicle}/photos/{vehiclePhoto}', [DriverVehicleMediaController::class, 'destroyPhoto'])->middleware('throttle:driver-write');
            Route::get('/assignments', [DriverAssignmentResponseController::class, 'index'])->middleware('throttle:authenticated-read');
            Route::get('/assignments/{driverAssignment}', [DriverAssignmentResponseController::class, 'show'])->middleware('throttle:authenticated-read');
            Route::patch('/assignments/{driverAssignment}/accept', [DriverAssignmentResponseController::class, 'accept'])->middleware('throttle:driver-write');
            Route::patch('/assignments/{driverAssignment}/reject', [DriverAssignmentResponseController::class, 'reject'])->middleware('throttle:driver-write');
            Route::patch('/assignments/{driverAssignment}/start-trip', [DriverAssignmentResponseController::class, 'startTrip'])->middleware('throttle:driver-write');
            Route::patch('/assignments/{driverAssignment}/complete-trip', [DriverAssignmentResponseController::class, 'completeTrip'])->middleware('throttle:driver-write');
            Route::post('/device-tokens', [DriverDeviceTokenController::class, 'store'])->middleware('throttle:driver-write');
            Route::delete('/device-tokens', [DriverDeviceTokenController::class, 'destroy'])->middleware('throttle:driver-write');
            Route::get('/points/summary', [DriverPointController::class, 'summary'])->middleware('throttle:authenticated-read');
            Route::get('/points/ledger', [DriverPointController::class, 'ledger'])->middleware('throttle:authenticated-read');
            Route::get('/withdrawals', [DriverPointController::class, 'withdrawals'])->middleware('throttle:authenticated-read');
            Route::post('/withdrawals', [DriverPointController::class, 'requestWithdrawal'])->middleware('throttle:withdrawal-request');
        });

    Route::prefix('admin')
        ->middleware(['auth:sanctum', 'role:admin'])
        ->group(function (): void {
            Route::get('dashboard', [DashboardController::class, 'show'])->middleware('throttle:authenticated-read');
            Route::prefix('reports/export')->middleware('throttle:authenticated-read')->group(function (): void {
                Route::get('bookings', [ReportExportController::class, 'bookings']);
                Route::get('payments', [ReportExportController::class, 'payments']);
                Route::get('drivers', [ReportExportController::class, 'drivers']);
                Route::get('withdrawals', [ReportExportController::class, 'withdrawals']);
            });
            Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('throttle:authenticated-read');
            Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->middleware('throttle:authenticated-read');

            Route::apiResource('tour-packages', AdminTourPackageController::class)->middlewareFor(['index', 'show'], 'throttle:authenticated-read')->middlewareFor(['store', 'update', 'destroy'], 'throttle:admin-write');
            Route::apiResource('vehicles', AdminVehicleController::class)->middlewareFor(['index', 'show'], 'throttle:authenticated-read')->middlewareFor(['store', 'update', 'destroy'], 'throttle:admin-write');
            Route::get('drivers', [DriverVerificationController::class, 'index'])->middleware('throttle:authenticated-read');
            Route::get('drivers/{driverProfile}', [DriverVerificationController::class, 'show'])->middleware('throttle:authenticated-read');
            Route::patch('drivers/{driverProfile}/verification', [DriverVerificationController::class, 'update'])->middleware('throttle:admin-write');
            Route::patch('driver-vehicles/{vehicle}/verification', [DriverVehicleVerificationController::class, 'update'])->middleware('throttle:admin-write');
            Route::patch('driver-documents/{driverDocument}/verification', [DocumentVerificationController::class, 'updateDriverDocument'])->middleware('throttle:admin-write');
            Route::patch('driver-vehicles/{vehicle}/documents/{vehicleDocument}/verification', [DocumentVerificationController::class, 'updateVehicleDocument'])->middleware('throttle:admin-write');

            Route::get('bookings', [AdminBookingController::class, 'index'])->middleware('throttle:authenticated-read');
            Route::get('bookings/{booking}', [AdminBookingController::class, 'show'])->middleware('throttle:authenticated-read');
            Route::patch('bookings/{booking}/status', [AdminBookingController::class, 'updateStatus'])->middleware('throttle:admin-write');
            Route::post('bookings/{booking}/driver-assignments', [DriverAssignmentController::class, 'store'])->middleware('throttle:admin-write');
            Route::patch('bookings/{booking}/driver-assignments/{driverAssignment}/cancel', [DriverAssignmentController::class, 'cancel'])->middleware('throttle:admin-write');

            Route::get('travel-groups', [TravelGroupController::class, 'index'])->middleware('throttle:authenticated-read');
            Route::post('travel-groups', [TravelGroupController::class, 'store'])->middleware('throttle:admin-write');
            Route::get('travel-groups/{travelGroup}', [TravelGroupController::class, 'show'])->middleware('throttle:authenticated-read');
            Route::post('travel-groups/{travelGroup}/bookings', [TravelGroupController::class, 'attachBooking'])->middleware('throttle:admin-write');
            Route::get('bookings/{booking}/participant-allocations', [TravelGroupController::class, 'allocations'])->middleware('throttle:authenticated-read');
            Route::put('bookings/{booking}/participant-allocations', [TravelGroupController::class, 'allocateParticipant'])->middleware('throttle:admin-write');

            Route::get('payments', [AdminPaymentController::class, 'index'])->middleware('throttle:authenticated-read');
            Route::get('payments/{payment}', [AdminPaymentController::class, 'show'])->middleware('throttle:authenticated-read');
            Route::patch('payments/{payment}/verification', [AdminPaymentController::class, 'update'])->middleware('throttle:admin-write');

            Route::get('withdrawals', [AdminWithdrawalController::class, 'index'])->middleware('throttle:authenticated-read');
            Route::get('withdrawals/{withdrawal}', [AdminWithdrawalController::class, 'show'])->middleware('throttle:authenticated-read');
            Route::patch('withdrawals/{withdrawal}', [AdminWithdrawalController::class, 'update'])->middleware('throttle:admin-write');
        });
});
