<?php

use App\Http\Controllers\Api\V1\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Api\V1\Admin\DriverAssignmentController;
use App\Http\Controllers\Api\V1\Admin\DriverVehicleVerificationController;
use App\Http\Controllers\Api\V1\Admin\DriverVerificationController;
use App\Http\Controllers\Api\V1\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Api\V1\Admin\TourPackageController as AdminTourPackageController;
use App\Http\Controllers\Api\V1\Admin\VehicleController as AdminVehicleController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\CustomerProfileController;
use App\Http\Controllers\Api\V1\CustomerRegistrationController;
use App\Http\Controllers\Api\V1\DriverAssignmentController as DriverAssignmentResponseController;
use App\Http\Controllers\Api\V1\DriverRegistrationController;
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
    });

    Route::post('/driver/register', [DriverRegistrationController::class, 'store']);
    Route::post('/customers/register', [CustomerRegistrationController::class, 'store']);

    Route::get('/tour-packages', [TourPackageController::class, 'index']);
    Route::get('/tour-packages/{tourPackage}', [TourPackageController::class, 'show']);

    Route::prefix('auth')->group(function (): void {
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::prefix('customer')
        ->middleware(['auth:sanctum', 'role:customer'])
        ->group(function (): void {
            Route::get('/profile', [CustomerProfileController::class, 'show']);
            Route::patch('/profile', [CustomerProfileController::class, 'update']);

            Route::get('/bookings', [BookingController::class, 'index']);
            Route::post('/bookings', [BookingController::class, 'store']);
            Route::get('/bookings/{booking}', [BookingController::class, 'show']);

            Route::get('/payments', [PaymentController::class, 'index']);
            Route::post('/bookings/{booking}/payments', [PaymentController::class, 'store']);
            Route::get('/payments/{payment}', [PaymentController::class, 'show']);
        });

    Route::prefix('driver')
        ->middleware(['auth:sanctum', 'role:driver'])
        ->group(function (): void {
            Route::get('/assignments', [DriverAssignmentResponseController::class, 'index']);
            Route::get('/assignments/{driverAssignment}', [DriverAssignmentResponseController::class, 'show']);
            Route::patch('/assignments/{driverAssignment}/accept', [DriverAssignmentResponseController::class, 'accept']);
            Route::patch('/assignments/{driverAssignment}/reject', [DriverAssignmentResponseController::class, 'reject']);
        });

    Route::prefix('admin')
        ->middleware(['auth:sanctum', 'role:admin'])
        ->group(function (): void {
            Route::apiResource('tour-packages', AdminTourPackageController::class);
            Route::apiResource('vehicles', AdminVehicleController::class);

            Route::get('drivers', [DriverVerificationController::class, 'index']);
            Route::get('drivers/{driverProfile}', [DriverVerificationController::class, 'show']);
            Route::patch('drivers/{driverProfile}/verification', [DriverVerificationController::class, 'update']);
            Route::patch('driver-vehicles/{vehicle}/verification', [DriverVehicleVerificationController::class, 'update']);

            Route::get('bookings', [AdminBookingController::class, 'index']);
            Route::get('bookings/{booking}', [AdminBookingController::class, 'show']);
            Route::patch('bookings/{booking}/status', [AdminBookingController::class, 'updateStatus']);
            Route::post('bookings/{booking}/driver-assignments', [DriverAssignmentController::class, 'store']);
            Route::patch('bookings/{booking}/driver-assignments/{driverAssignment}/cancel', [DriverAssignmentController::class, 'cancel']);

            Route::get('payments', [AdminPaymentController::class, 'index']);
            Route::get('payments/{payment}', [AdminPaymentController::class, 'show']);
            Route::patch('payments/{payment}/verification', [AdminPaymentController::class, 'update']);
        });
});
