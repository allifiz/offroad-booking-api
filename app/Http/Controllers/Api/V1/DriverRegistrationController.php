<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DriverStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VehicleOwnershipType;
use App\Enums\VehicleStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\RegisterDriverRequest;
use App\Http\Resources\DriverRegistrationResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DriverRegistrationController extends Controller
{
    public function store(RegisterDriverRequest $request): JsonResponse
    {
        $data = $request->validated();
        $storedPaths = [];

        try {
            $user = DB::transaction(function () use ($request, $data, &$storedPaths): User {
                $profilePhotoPath = $request->file('profile_photo')->store('drivers/profile-photos', 'public');
                $storedPaths[] = $profilePhotoPath;

                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'password' => $data['password'],
                    'role' => UserRole::DRIVER,
                    'status' => UserStatus::ACTIVE,
                ]);

                $profile = $user->driverProfile()->create([
                    'status' => DriverStatus::UNAVAILABLE,
                    'verification_status' => VerificationStatus::PENDING,
                    'profile_photo_path' => $profilePhotoPath,
                    'identity_number' => $data['identity_number'],
                    'license_number' => $data['license_number'],
                    'address' => $data['address'],
                    'date_of_birth' => $data['date_of_birth'],
                ]);

                foreach ($data['driver_documents'] as $index => $documentData) {
                    $path = $request->file("driver_documents.$index.file")->store('drivers/documents', 'public');
                    $storedPaths[] = $path;
                    $profile->documents()->create([
                        'type' => $documentData['type'],
                        'file_path' => $path,
                        'document_number' => $documentData['document_number'] ?? null,
                        'expires_at' => $documentData['expires_at'] ?? null,
                    ]);
                }

                $vehicleData = $data['vehicle'];
                $vehicle = $profile->vehicles()->create([
                    'name' => $vehicleData['name'],
                    'plate_number' => strtoupper($vehicleData['plate_number']),
                    'brand' => $vehicleData['brand'] ?? null,
                    'model' => $vehicleData['model'] ?? null,
                    'year' => $vehicleData['year'] ?? null,
                    'capacity' => $vehicleData['capacity'],
                    'status' => VehicleStatus::UNAVAILABLE,
                    'ownership_type' => VehicleOwnershipType::DRIVER,
                    'verification_status' => VerificationStatus::PENDING,
                    'notes' => $vehicleData['notes'] ?? null,
                ]);

                foreach ($data['vehicle_documents'] as $index => $documentData) {
                    $path = $request->file("vehicle_documents.$index.file")->store('vehicles/documents', 'public');
                    $storedPaths[] = $path;
                    $vehicle->documents()->create([
                        'type' => $documentData['type'],
                        'file_path' => $path,
                        'document_number' => $documentData['document_number'] ?? null,
                        'expires_at' => $documentData['expires_at'] ?? null,
                    ]);
                }

                foreach ($request->file('vehicle_photos', []) as $index => $photo) {
                    $path = $photo->store('vehicles/photos', 'public');
                    $storedPaths[] = $path;
                    $vehicle->photos()->create([
                        'file_path' => $path,
                        'type' => $index === 0 ? 'front' : 'other',
                        'sort_order' => $index,
                    ]);
                }

                return $user;
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);
            throw $exception;
        }

        $user->load('driverProfile.documents', 'driverProfile.vehicles.documents', 'driverProfile.vehicles.photos');

        return response()->json([
            'success' => true,
            'message' => 'Registrasi driver berhasil. Data menunggu verifikasi admin.',
            'data' => new DriverRegistrationResource($user),
        ], Response::HTTP_CREATED);
    }
}
