<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourPackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'meeting_point' => $this->meeting_point,
            'duration_minutes' => $this->duration_minutes,
            'minimum_participants' => $this->minimum_participants,
            'maximum_participants' => $this->maximum_participants,
            'price_per_person' => $this->price_per_person,
            'status' => $this->status->value,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
