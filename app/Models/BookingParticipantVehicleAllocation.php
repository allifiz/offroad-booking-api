<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingParticipantVehicleAllocation extends Model
{
    protected $fillable = [
        'booking_id',
        'booking_participant_id',
        'driver_assignment_id',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(BookingParticipant::class, 'booking_participant_id');
    }

    public function driverAssignment(): BelongsTo
    {
        return $this->belongsTo(DriverAssignment::class);
    }
}
