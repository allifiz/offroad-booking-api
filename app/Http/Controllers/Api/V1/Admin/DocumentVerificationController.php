<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\DriverDocument;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class DocumentVerificationController extends Controller
{
    public function updateDriverDocument(Request $request, DriverDocument $driverDocument): JsonResponse
    {
        $validated = $this->validateVerification($request);
        $isRejected = $validated['verification_status'] === VerificationStatus::REJECTED->value;

        $driverDocument->update([
            'verification_status' => $validated['verification_status'],
            'rejection_reason' => $isRejected ? $validated['rejection_reason'] : null,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $isRejected ? 'Dokumen driver berhasil ditolak.' : 'Dokumen driver berhasil disetujui.',
            'data' => $driverDocument->refresh()->load(['driverProfile.user', 'verifier']),
        ]);
    }

    public function updateVehicleDocument(
        Request $request,
        Vehicle $vehicle,
        VehicleDocument $vehicleDocument,
    ): JsonResponse {
        abort_unless($vehicleDocument->vehicle_id === $vehicle->id, Response::HTTP_NOT_FOUND);

        $validated = $this->validateVerification($request);
        $isRejected = $validated['verification_status'] === VerificationStatus::REJECTED->value;

        $vehicleDocument->update([
            'verification_status' => $validated['verification_status'],
            'rejection_reason' => $isRejected ? $validated['rejection_reason'] : null,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $isRejected ? 'Dokumen kendaraan berhasil ditolak.' : 'Dokumen kendaraan berhasil disetujui.',
            'data' => $vehicleDocument->refresh()->load(['vehicle.driverProfile.user', 'verifier']),
        ]);
    }

    private function validateVerification(Request $request): array
    {
        return $request->validate([
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
    }
}
