<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BookingParticipant extends Model
{
    protected $fillable = ['booking_id', 'user_id', 'name', 'phone', 'is_group_leader'];

    protected function casts(): array
    {
        return ['is_group_leader' => 'boolean'];
    }

    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function vehicleAllocation(): HasOne { return $this->hasOne(BookingParticipantVehicleAllocation::class); }
}
