<?php

namespace App\Http\Requests\Api\V1\Driver;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'identity_number' => ['required', 'string', 'max:30', 'unique:driver_profiles,identity_number'],
            'license_number' => ['required', 'string', 'max:100', 'unique:driver_profiles,license_number'],
            'address' => ['required', 'string'],
            'date_of_birth' => ['required', 'date', 'before:-18 years'],
            'profile_photo' => ['required', 'image', 'max:3072'],

            'driver_documents' => ['required', 'array', 'min:2'],
            'driver_documents.*.type' => ['required', 'string', 'max:50', 'distinct'],
            'driver_documents.*.file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'driver_documents.*.document_number' => ['nullable', 'string', 'max:100'],
            'driver_documents.*.expires_at' => ['nullable', 'date', 'after:today'],

            'vehicle.name' => ['required', 'string', 'max:100'],
            'vehicle.plate_number' => ['required', 'string', 'max:20', 'unique:vehicles,plate_number'],
            'vehicle.brand' => ['nullable', 'string', 'max:100'],
            'vehicle.model' => ['nullable', 'string', 'max:100'],
            'vehicle.year' => ['nullable', 'integer', 'min:1950', 'max:'.(date('Y') + 1)],
            'vehicle.capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'vehicle.notes' => ['nullable', 'string'],

            'vehicle_documents' => ['required', 'array', 'min:1'],
            'vehicle_documents.*.type' => ['required', 'string', 'max:50', 'distinct'],
            'vehicle_documents.*.file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'vehicle_documents.*.document_number' => ['nullable', 'string', 'max:100'],
            'vehicle_documents.*.expires_at' => ['nullable', 'date', 'after:today'],

            'vehicle_photos' => ['required', 'array', 'min:1', 'max:8'],
            'vehicle_photos.*' => ['required', 'image', 'max:4096'],
        ];
    }
}
