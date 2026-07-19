<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $fillable = ['booking_code', 'customer_id', 'tour_package_id', 'travel_group_id', 'tour_date', 'participant_count', 'total_amount', 'status', 'payment_status', 'notes'];

    protected function casts(): array
    {
        return [
            'tour_date' => 'date',
            'participant_count' => 'integer',
            'total_amount' => 'decimal:2',
            'status' => BookingStatus::class,
            'payment_status' => PaymentStatus::class,
        ];
    }

    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_id'); }
    public function tourPackage(): BelongsTo { return $this->belongsTo(TourPackage::class); }
    public function travelGroup(): BelongsTo { return $this->belongsTo(TravelGroup::class); }
    public function participants(): HasMany { return $this->hasMany(BookingParticipant::class); }
    public function driverAssignments(): HasMany { return $this->hasMany(DriverAssignment::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
}
