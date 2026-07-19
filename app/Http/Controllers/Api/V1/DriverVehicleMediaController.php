<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\VehicleStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Models\VehiclePhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DriverVehicleMediaController extends Controller
{
    public function storeDocument(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->ensureOwnership($request, $vehicle);

        $validated = $request->validate([
            'type' => ['required', 'string', 'max:50'],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $newPath = $request->file('file')->store('vehicles/documents', 'public');
        $oldPath = null;

        try {
            $document = DB::transaction(function () use ($vehicle, $validated, $newPath, &$oldPath): VehicleDocument {
                $existing = $vehicle->documents()
                    ->where('type', $validated['type'])
                    ->lockForUpdate()
                    ->first();

                $oldPath = $existing?->file_path;

                $document = $vehicle->documents()->updateOrCreate(
                    ['type' => $validated['type']],
                    [
                        'file_path' => $newPath,
                        'document_number' => $validated['document_number'] ?? null,
                        'expires_at' => $validated['expires_at'] ?? null,
                        'verification_status' => VerificationStatus::PENDING,
                        'rejection_reason' => null,
                        'verified_by' => null,
                        'verified_at' => null,
                    ],
                );

                $this->resetVehicleVerification($vehicle);

                return $document;
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newPath);
            throw $exception;
        }

        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dokumen kendaraan berhasil disimpan dan menunggu verifikasi admin.',
            'data' => $document->refresh(),
        ], Response::HTTP_CREATED);
    }

    public function storePhoto(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->ensureOwnership($request, $vehicle);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['front', 'back', 'left', 'right', 'interior', 'other'])],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $path = $request->file('photo')->store('vehicles/photos', 'public');

        try {
            $photo = DB::transaction(function () use ($vehicle, $validated, $path): VehiclePhoto {
                $photo = $vehicle->photos()->create([
                    'type' => $validated['type'],
                    'file_path' => $path,
                    'sort_order' => $validated['sort_order'] ?? ($vehicle->photos()->max('sort_order') + 1),
                ]);

                $this->resetVehicleVerification($vehicle);

                return $photo;
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

        return response()->json([
            'success' => true,
            'message' => 'Foto kendaraan berhasil ditambahkan dan kendaraan menunggu verifikasi ulang admin.',
            'data' => $photo,
        ], Response::HTTP_CREATED);
    }

    public function reorderPhotos(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->ensureOwnership($request, $vehicle);

        $validated = $request->validate([
            'photos' => ['required', 'array', 'min:1'],
            'photos.*.id' => ['required', 'integer', 'distinct', 'exists:vehicle_photos,id'],
            'photos.*.sort_order' => ['required', 'integer', 'min:0', 'distinct'],
        ]);

        $ownedPhotoIds = $vehicle->photos()->whereIn('id', collect($validated['photos'])->pluck('id'))->pluck('id');
        abort_unless($ownedPhotoIds->count() === count($validated['photos']), Response::HTTP_NOT_FOUND);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['photos'] as $item) {
                VehiclePhoto::query()->whereKey($item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Urutan foto kendaraan berhasil diperbarui.',
            'data' => $vehicle->photos()->get(),
        ]);
    }

    public function destroyPhoto(Request $request, Vehicle $vehicle, VehiclePhoto $vehiclePhoto): JsonResponse
    {
        $this->ensureOwnership($request, $vehicle);
        abort_unless($vehiclePhoto->vehicle_id === $vehicle->id, Response::HTTP_NOT_FOUND);

        $path = $vehiclePhoto->file_path;

        DB::transaction(function () use ($vehicle, $vehiclePhoto): void {
            $vehiclePhoto->delete();
            $this->resetVehicleVerification($vehicle);
        });

        Storage::disk('public')->delete($path);

        return response()->json([
            'success' => true,
            'message' => 'Foto kendaraan berhasil dihapus dan kendaraan menunggu verifikasi ulang admin.',
        ]);
    }

    private function ensureOwnership(Request $request, Vehicle $vehicle): void
    {
        $profile = $request->user()->driverProfile;
        abort_unless($profile && $vehicle->driver_profile_id === $profile->id, Response::HTTP_NOT_FOUND);
    }

    private function resetVehicleVerification(Vehicle $vehicle): void
    {
        $vehicle->update([
            'status' => VehicleStatus::UNAVAILABLE,
            'verification_status' => VerificationStatus::PENDING,
            'rejection_reason' => null,
            'verified_by' => null,
            'verified_at' => null,
        ]);
    }
}
