<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\TourPackageStatus;
use App\Http\Controllers\Controller;
use App\Models\TourPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TourPackageController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(TourPackageStatus::class)],
        ]);

        $tourPackages = TourPackage::query()
            ->withCount('bookings')
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('meeting_point', 'like', "%{$search}%");
                });
            })
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.tour-packages.index', compact('tourPackages'));
    }

    public function create(): View
    {
        return view('admin.tour-packages.form', [
            'tourPackage' => new TourPackage(),
            'statuses' => TourPackageStatus::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['slug'] = filled($data['slug'] ?? null)
            ? $data['slug']
            : Str::slug($data['name']).'-'.Str::lower(Str::random(5));

        $tourPackage = TourPackage::query()->create($data);

        return redirect()
            ->route('admin.tour-packages.edit', $tourPackage)
            ->with('success', 'Paket wisata berhasil dibuat.');
    }

    public function edit(TourPackage $tourPackage): View
    {
        return view('admin.tour-packages.form', [
            'tourPackage' => $tourPackage,
            'statuses' => TourPackageStatus::cases(),
        ]);
    }

    public function update(Request $request, TourPackage $tourPackage): RedirectResponse
    {
        $data = $this->validatedData($request, $tourPackage);
        $data['slug'] = filled($data['slug'] ?? null)
            ? $data['slug']
            : Str::slug($data['name']).'-'.Str::lower(Str::random(5));

        $tourPackage->update($data);

        return redirect()
            ->route('admin.tour-packages.edit', $tourPackage)
            ->with('success', 'Paket wisata berhasil diperbarui.');
    }

    public function destroy(TourPackage $tourPackage): RedirectResponse
    {
        if ($tourPackage->bookings()->exists()) {
            throw ValidationException::withMessages([
                'tour_package' => ['Paket wisata yang sudah memiliki booking tidak dapat dihapus. Nonaktifkan paket sebagai gantinya.'],
            ]);
        }

        $tourPackage->delete();

        return redirect()
            ->route('admin.tour-packages.index')
            ->with('success', 'Paket wisata berhasil dihapus.');
    }

    private function validatedData(Request $request, ?TourPackage $tourPackage = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('tour_packages', 'slug')->ignore($tourPackage)],
            'description' => ['nullable', 'string'],
            'meeting_point' => ['nullable', 'string', 'max:255'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'minimum_participants' => ['required', 'integer', 'min:1'],
            'maximum_participants' => ['nullable', 'integer', 'gte:minimum_participants'],
            'price_per_person' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(TourPackageStatus::class)],
        ]);
    }
}
