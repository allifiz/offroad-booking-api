<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\DriverDocument;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class DriverDocumentController extends Controller
{
    public function reuploadDriverDocument(Request $request, DriverDocument $driverDocument): JsonResponse
    {
        $profile = $request->user()->driverProfile;
        abort_unless($profile && $driverDocument->driver_profile_id === $profile->id, Response::HTTP_NOT_FOUND);

        if ($driverDocument->verification_status !== VerificationStatus::REJECTED) {
            throw ValidationException::withMessages([
                'document' => ['Hanya dokumen driver yang ditolak yang dapat diunggah ulang.'],
            ]);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'document_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:today'],
        ]);

        $oldPath = $driverDocument->file_path;
        $newPath = $request->file('file')->store('drivers/documents', 'public');

        try {
            DB::transaction(function () use ($driverDocument, $validated, $newPath): void {
                $driverDocument->update([
                    'file_path' => $newPath,
                    'document_number' => $validated['document_number'] ?? $driverDocument->document_number,
                    'expires_at' => array_key_exists('expires_at', $validated) ? $validated['expires_at'] : $driverDocument->expires_at,
                    'verification_status' => VerificationStatus::PENDING,
                    'rejection_reason' => null,
                    'verified_by' => null,
                    'verified_at' => null,
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($newPath);
            throw $exception;
        }

        Storage::disk('public')->delete($oldPath);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen driver berhasil diunggah ulang dan menunggu verifikasi admin.',
            'data' => $driverDocument->refresh(),
        ]);
    }

    public function reuploadVehicleDocument(Request $request, Vehicle $vehicle, VehicleDocument $vehicleDocument): JsonResponse
    {
        $profile = $request->user()->driverProfile;
        abort_unless(
            $profile
            && $vehicle->driver_profile_id === $profile->id
            && $vehicleDocument->vehicle_id === $vehicle->id,
            Response::HTTP_NOT_FOUND,
        );

        if ($vehicleDocument->verification_status !== VerificationStatus::REJECTED) {
            throw ValidationException::withMessages([
                'document' => ['Hanya dokumen kendaraan yang ditolak yang dapat diunggah ulang.'],
            ]);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'document_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:today'],
        ]);

        $oldPath = $vehicleDocument->file_path;
        $newPath = $request->file('file')->store('vehicles/documents', 'public');

        try {
            DB::transaction(function () use ($vehicleDocument, $validated, $newPath): void {
                $vehicleDocument->update([
                    'file_path' => $newPath,
                    'document_number' => $validated['document_number'] ?? $vehicleDocument->document_number,
                    'expires_at' => array_key_exists('expires_at', $validated) ? $validated['expires_at'] : $vehicleDocument->expires_at,
                    'verification_status' => VerificationStatus::PENDING,
                    'rejection_reason' => null,
                    'verified_by' => null,
                    'verified_at' => null,
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($newPath);
            throw $exception;
        }

        Storage::disk('public')->delete($oldPath);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen kendaraan berhasil diunggah ulang dan menunggu verifikasi admin.',
            'data' => $vehicleDocument->refresh(),
        ]);
    }
}
