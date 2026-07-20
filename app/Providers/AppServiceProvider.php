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
use App\Observers\OperationalNotificationObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiters();

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

        foreach ([
            Booking::class,
            DriverAssignment::class,
            DriverProfile::class,
            Payment::class,
            Vehicle::class,
            Withdrawal::class,
        ] as $model) {
            $model::observe(OperationalNotificationObserver::class);
        }
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('auth-login', function (Request $request): Limit {
            $email = Str::lower((string) $request->input('email'));

            return Limit::perMinute(5)
                ->by($email.'|'.$request->ip());
        });

        RateLimiter::for('public-registration', fn (Request $request): Limit => Limit::perHour(3)
            ->by($request->ip()));

        RateLimiter::for('authenticated-read', fn (Request $request): Limit => Limit::perMinute(120)
            ->by((string) ($request->user()?->id ?? $request->ip())));

        RateLimiter::for('customer-write', fn (Request $request): Limit => Limit::perMinute(20)
            ->by((string) ($request->user()?->id ?? $request->ip())));

        RateLimiter::for('driver-write', fn (Request $request): Limit => Limit::perMinute(30)
            ->by((string) ($request->user()?->id ?? $request->ip())));

        RateLimiter::for('file-upload', fn (Request $request): Limit => Limit::perMinute(10)
            ->by((string) ($request->user()?->id ?? $request->ip())));

        RateLimiter::for('withdrawal-request', fn (Request $request): Limit => Limit::perHour(3)
            ->by((string) ($request->user()?->id ?? $request->ip())));

        RateLimiter::for('admin-write', fn (Request $request): Limit => Limit::perMinute(60)
            ->by((string) ($request->user()?->id ?? $request->ip())));
    }
}
