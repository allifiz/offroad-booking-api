<?php

namespace App\Models;

use App\Enums\TourPackageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TourPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'meeting_point',
        'duration_minutes',
        'minimum_participants',
        'maximum_participants',
        'price_per_person',
        'status',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'minimum_participants' => 'integer',
            'maximum_participants' => 'integer',
            'price_per_person' => 'decimal:2',
            'status' => TourPackageStatus::class,
        ];
    }
}
