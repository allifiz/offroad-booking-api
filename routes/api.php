<?php

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
});