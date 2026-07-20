<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\DriverStatus;
use App\Enums\VehicleStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\DriverProfile;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DriverVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'verification_status' => ['nullable', Rule::enum(VerificationStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $drivers = DriverProfile::query()
            ->with(['user'])
            ->withCount(['documents', 'vehicles'])
            ->when($validated['verification_status'] ?? null, fn ($query, $status) => $query->where('verification_status', $status))
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('license_number', 'like', "%{$search}%")
                        ->orWhere('identity_number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.drivers.index', compact('drivers'));
    }

    public function show(DriverProfile $driverProfile): View
    {
        $driverProfile->load(['user', 'documents', 'vehicles.documents', 'vehicles.photos', 'verifier']);

        return view('admin.drivers.show', compact('driverProfile'));
    }

    public function updateDriver(Request $request, DriverProfile $driverProfile): RedirectResponse
    {
        $validated = $request->validate($this->verificationRules($request));
        $approved = $validated['verification_status'] === VerificationStatus::APPROVED->value;

        DB::transaction(function () use ($request, $driverProfile, $validated, $approved): void {
            $driverProfile->update([
                'verification_status' => $validated['verification_status'],
                'status' => $approved ? DriverStatus::AVAILABLE : DriverStatus::UNAVAILABLE,
                'rejection_reason' => $approved ? null : $validated['rejection_reason'],
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);
        });

        return back()->with('success', $approved ? 'Driver berhasil disetujui dan diaktifkan.' : 'Driver berhasil ditolak.');
    }

    public function updateVehicle(Request $request, DriverProfile $driverProfile, Vehicle $vehicle): RedirectResponse
    {
        abort_unless($vehicle->driver_profile_id === $driverProfile->id, 404);
        $validated = $request->validate($this->verificationRules($request));
        $approved = $validated['verification_status'] === VerificationStatus::APPROVED->value;

        $vehicle->update([
            'verification_status' => $validated['verification_status'],
            'status' => $approved ? VehicleStatus::AVAILABLE : VehicleStatus::UNAVAILABLE,
            'rejection_reason' => $approved ? null : $validated['rejection_reason'],
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return back()->with('success', $approved ? 'Kendaraan berhasil disetujui dan diaktifkan.' : 'Kendaraan berhasil ditolak.');
    }

    private function verificationRules(Request $request): array
    {
        return [
            'verification_status' => ['required', Rule::in([
                VerificationStatus::APPROVED->value,
                VerificationStatus::REJECTED->value,
            ])],
            'rejection_reason' => [
                Rule::requiredIf($request->input('verification_status') === VerificationStatus::REJECTED->value),
                'nullable', 'string', 'max:1000',
            ],
        ];
    }
}
