<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\DriverProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DriverVerificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'verification_status' => ['nullable', Rule::enum(VerificationStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $drivers = DriverProfile::query()
            ->with(['user', 'documents', 'vehicles.documents', 'vehicles.photos'])
            ->when(
                $request->filled('verification_status'),
                fn ($query) => $query->where('verification_status', $request->string('verification_status')),
            )
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search): void {
                    $query->where('license_number', 'like', "%{$search}%")
                        ->orWhere('identity_number', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $drivers,
        ]);
    }

    public function show(DriverProfile $driverProfile): JsonResponse
    {
        $driverProfile->load(['user', 'documents', 'vehicles.documents', 'vehicles.photos', 'verifier']);

        return response()->json([
            'success' => true,
            'data' => $driverProfile,
        ]);
    }

    public function update(Request $request, DriverProfile $driverProfile): JsonResponse
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

        $driverProfile->update([
            'verification_status' => $validated['verification_status'],
            'rejection_reason' => $isRejected ? $validated['rejection_reason'] : null,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $isRejected
                ? 'Driver berhasil ditolak.'
                : 'Driver berhasil disetujui.',
            'data' => $driverProfile->refresh()->load(['user', 'documents', 'vehicles']),
        ]);
    }
}
