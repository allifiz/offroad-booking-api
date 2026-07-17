<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverRegistrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'driver_profile' => [
                'id' => $this->driverProfile->id,
                'availability_status' => $this->driverProfile->status->value,
                'verification_status' => $this->driverProfile->verification_status->value,
                'profile_photo_url' => asset('storage/'.$this->driverProfile->profile_photo_path),
                'documents' => $this->driverProfile->documents->map(fn ($document) => [
                    'id' => $document->id,
                    'type' => $document->type,
                    'verification_status' => $document->verification_status->value,
                ]),
                'vehicles' => $this->driverProfile->vehicles->map(fn ($vehicle) => [
                    'id' => $vehicle->id,
                    'name' => $vehicle->name,
                    'plate_number' => $vehicle->plate_number,
                    'ownership_type' => $vehicle->ownership_type->value,
                    'availability_status' => $vehicle->status->value,
                    'verification_status' => $vehicle->verification_status->value,
                    'documents_count' => $vehicle->documents->count(),
                    'photos_count' => $vehicle->photos->count(),
                ]),
            ],
        ];
    }
}
