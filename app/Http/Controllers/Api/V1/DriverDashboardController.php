<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DriverStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class DriverDashboardController extends Controller
{
    public function showProfile(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'driverProfile.documents',
            'driverProfile.vehicles.documents',
            'driverProfile.vehicles.photos',
        ]);

        abort_unless($user->driverProfile, Response::HTTP_NOT_FOUND);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $profile = $request->user()->driverProfile;
        abort_unless($profile, Response::HTTP_NOT_FOUND);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'phone' => ['sometimes', 'required', 'string', 'max:30', 'unique:users,phone,'.$request->user()->id],
            'address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
        ]);

        DB::transaction(function () use ($request, $profile, $validated): void {
            $request->user()->update(array_filter([
                'name' => $validated['name'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ], fn ($value) => $value !== null));

            $profile->update(array_filter([
                'address' => $validated['address'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
            ], fn ($value) => $value !== null));
        });

        return response()->json([
            'success' => true,
            'message' => 'Profil driver berhasil diperbarui.',
            'data' => $request->user()->fresh()->load(['driverProfile.documents']),
        ]);
    }

    public function updateAvailability(Request $request): JsonResponse
    {
        $profile = $request->user()->driverProfile;
        abort_unless($profile, Response::HTTP_NOT_FOUND);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:available,unavailable'],
        ]);

        if ($profile->status === DriverStatus::SUSPENDED) {
            throw ValidationException::withMessages([
                'status' => ['Driver yang disuspend tidak dapat mengubah availability.'],
            ]);
        }

        if ($validated['status'] === DriverStatus::AVAILABLE->value
            && $profile->verification_status !== VerificationStatus::APPROVED) {
            throw ValidationException::withMessages([
                'status' => ['Driver harus berstatus approved sebelum dapat menjadi available.'],
            ]);
        }

        $profile->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Availability driver berhasil diperbarui.',
            'data' => $profile->refresh(),
        ]);
    }

    public function vehicles(Request $request): JsonResponse
    {
        $profile = $request->user()->driverProfile;
        abort_unless($profile, Response::HTTP_NOT_FOUND);

        $vehicles = $profile->vehicles()
            ->with(['documents', 'photos'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vehicles,
        ]);
    }

    public function showVehicle(Request $request, Vehicle $vehicle): JsonResponse
    {
        $profile = $request->user()->driverProfile;
        abort_unless($profile && $vehicle->driver_profile_id === $profile->id, Response::HTTP_NOT_FOUND);

        return response()->json([
            'success' => true,
            'data' => $vehicle->load(['documents', 'photos', 'verifier']),
        ]);
    }
}
