<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\VehicleOwnershipType;
use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Models\DriverProfile;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(VehicleStatus::class)],
            'ownership_type' => ['nullable', Rule::enum(VehicleOwnershipType::class)],
        ]);

        $vehicles = Vehicle::query()
            ->with('driverProfile.user')
            ->withCount('driverAssignments')
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('plate_number', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhereHas('driverProfile.user', function ($query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['ownership_type'] ?? null, fn ($query, string $type) => $query->where('ownership_type', $type))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.vehicles.index', compact('vehicles'));
    }

    public function create(): View
    {
        return $this->formView(new Vehicle());
    }

    public function store(Request $request): RedirectResponse
    {
        $vehicle = Vehicle::query()->create($this->validatedData($request));

        return redirect()
            ->route('admin.vehicles.edit', $vehicle)
            ->with('success', 'Kendaraan berhasil dibuat.');
    }

    public function edit(Vehicle $vehicle): View
    {
        return $this->formView($vehicle);
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($this->validatedData($request, $vehicle));

        return redirect()
            ->route('admin.vehicles.edit', $vehicle)
            ->with('success', 'Kendaraan berhasil diperbarui.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        if ($vehicle->driverAssignments()->exists()) {
            throw ValidationException::withMessages([
                'vehicle' => ['Kendaraan yang sudah memiliki assignment tidak dapat dihapus. Ubah status menjadi inactive sebagai gantinya.'],
            ]);
        }

        $vehicle->delete();

        return redirect()
            ->route('admin.vehicles.index')
            ->with('success', 'Kendaraan berhasil dihapus.');
    }

    private function formView(Vehicle $vehicle): View
    {
        $drivers = DriverProfile::query()
            ->with('user')
            ->orderByDesc('id')
            ->get();

        return view('admin.vehicles.form', [
            'vehicle' => $vehicle,
            'drivers' => $drivers,
            'statuses' => VehicleStatus::cases(),
            'ownershipTypes' => VehicleOwnershipType::cases(),
        ]);
    }

    private function validatedData(Request $request, ?Vehicle $vehicle = null): array
    {
        return $request->validate([
            'driver_profile_id' => ['nullable', 'integer', 'exists:driver_profiles,id'],
            'ownership_type' => ['required', Rule::enum(VehicleOwnershipType::class)],
            'name' => ['required', 'string', 'max:100'],
            'plate_number' => ['required', 'string', 'max:20', Rule::unique('vehicles', 'plate_number')->ignore($vehicle)],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['required', Rule::enum(VehicleStatus::class)],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
