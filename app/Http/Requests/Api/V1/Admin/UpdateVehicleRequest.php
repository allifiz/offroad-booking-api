<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\VehicleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vehicle = $this->route('vehicle');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'plate_number' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('vehicles', 'plate_number')->ignore($vehicle)],
            'brand' => ['sometimes', 'nullable', 'string', 'max:100'],
            'model' => ['sometimes', 'nullable', 'string', 'max:100'],
            'year' => ['sometimes', 'nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'required', Rule::enum(VehicleStatus::class)],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
