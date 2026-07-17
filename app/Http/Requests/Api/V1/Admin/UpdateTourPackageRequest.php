<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\TourPackageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTourPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tourPackage = $this->route('tourPackage');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:180', Rule::unique('tour_packages', 'slug')->ignore($tourPackage)],
            'description' => ['sometimes', 'nullable', 'string'],
            'meeting_point' => ['sometimes', 'nullable', 'string', 'max:255'],
            'duration_minutes' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'minimum_participants' => ['sometimes', 'required', 'integer', 'min:1'],
            'maximum_participants' => ['sometimes', 'nullable', 'integer', 'gte:minimum_participants'],
            'price_per_person' => ['sometimes', 'required', 'numeric', 'min:0'],
            'status' => ['sometimes', 'required', Rule::enum(TourPackageStatus::class)],
        ];
    }
}
