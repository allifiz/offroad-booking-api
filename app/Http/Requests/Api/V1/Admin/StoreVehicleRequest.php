<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\VehicleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'plate_number' => ['required', 'string', 'max:20', 'unique:vehicles,plate_number'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['required', Rule::enum(VehicleStatus::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
