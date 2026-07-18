<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DriverVehicleVerificationController extends Controller
{
    public function update(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'verification_status' => ['required', Rule::in([
                VerificationStatus::APPROVED->value,
                VerificationStatus::REJECTED->value,
            ])],
            'rejection_reason' => [
                Rule::requiredIf($request->input('verification_status') === VerificationStatus::REJECTED->value),
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $isRejected = $validated['verification_status'] === VerificationStatus::REJECTED->value;

        $vehicle->update([
            'verification_status' => $validated['verification_status'],
            'rejection_reason' => $isRejected ? $validated['rejection_reason'] : null,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $isRejected
                ? 'Kendaraan driver berhasil ditolak.'
                : 'Kendaraan driver berhasil disetujui.',
            'data' => $vehicle->refresh()->load(['driverProfile.user', 'documents', 'photos', 'verifier']),
        ]);
    }
}
