<?php

use App\Http\Controllers\Api\V1\Admin\TourPackageController as AdminTourPackageController;
use App\Http\Controllers\Api\V1\Admin\VehicleController as AdminVehicleController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DriverRegistrationController;
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

    Route::get('/tour-packages', [TourPackageController::class, 'index']);
    Route::get('/tour-packages/{tourPackage}', [TourPackageController::class, 'show']);

    Route::prefix('auth')->group(function (): void {
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::prefix('admin')
        ->middleware(['auth:sanctum', 'role:admin'])
        ->group(function (): void {
            Route::apiResource('tour-packages', AdminTourPackageController::class);
            Route::apiResource('vehicles', AdminVehicleController::class);
        });
});
