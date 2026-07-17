<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\TourPackageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTourPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:180', 'unique:tour_packages,slug'],
            'description' => ['nullable', 'string'],
            'meeting_point' => ['nullable', 'string', 'max:255'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'minimum_participants' => ['required', 'integer', 'min:1'],
            'maximum_participants' => ['nullable', 'integer', 'gte:minimum_participants'],
            'price_per_person' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(TourPackageStatus::class)],
        ];
    }
}
