<?php

namespace App\Models;

use App\Enums\DriverAssignmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverAssignment extends Model
{
    protected $fillable = ['booking_id', 'driver_id', 'vehicle_id', 'offered_by', 'status', 'offered_at', 'responded_at', 'rejection_reason'];

    protected function casts(): array
    {
        return [
            'status' => DriverAssignmentStatus::class,
            'offered_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
    public function driver(): BelongsTo { return $this->belongsTo(User::class, 'driver_id'); }
    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class); }
    public function offeredBy(): BelongsTo { return $this->belongsTo(User::class, 'offered_by'); }
}
