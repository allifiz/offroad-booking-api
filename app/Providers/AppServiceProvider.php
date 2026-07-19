<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\BookingParticipantVehicleAllocation;
use App\Models\DriverAssignment;
use App\Models\DriverDocument;
use App\Models\DriverProfile;
use App\Models\Payment;
use App\Models\TravelGroup;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Models\VehiclePhoto;
use App\Models\Withdrawal;
use App\Observers\AuditObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach ([
            Booking::class,
            BookingParticipantVehicleAllocation::class,
            DriverAssignment::class,
            DriverDocument::class,
            DriverProfile::class,
            Payment::class,
            TravelGroup::class,
            Vehicle::class,
            VehicleDocument::class,
            VehiclePhoto::class,
            Withdrawal::class,
        ] as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}
