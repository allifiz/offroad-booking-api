<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\DriverAssignmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\TourPackageStatus;
use App\Enums\VehicleOwnershipType;
use App\Enums\VehicleStatus;
use App\Enums\VerificationStatus;
use App\Models\Booking;
use App\Models\BookingParticipant;
use App\Models\DriverAssignment;
use App\Models\DriverProfile;
use App\Models\TourPackage;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWebBookingLifecycleFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_allocate_participant_and_complete_booking_through_shared_lifecycle(): void
    {
        config()->set('offroad.points_per_completed_trip', 100);

        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        $driver = User::factory()->driver()->create();
        $profile = DriverProfile::query()->create([
            'user_id' => $driver->id,
            'status' => 'available',
            'verification_status' => VerificationStatus::APPROVED,
            'available_points' => 0,
            'held_points' => 0,
        ]);
        $vehicle = Vehicle::query()->create([
            'driver_profile_id' => $profile->id,
            'ownership_type' => VehicleOwnershipType::DRIVER,
            'name' => 'Jeep Web Lifecycle',
            'plate_number' => 'B1234WEB',
            'brand' => 'Jeep',
            'model' => 'CJ7',
            'year' => 2020,
            'capacity' => 2,
            'status' => VehicleStatus::AVAILABLE,
            'verification_status' => VerificationStatus::APPROVED,
        ]);
        $package = TourPackage::query()->create([
            'name' => 'Paket Web Lifecycle',
            'slug' => 'paket-web-lifecycle',
            'description' => 'Test',
            'meeting_point' => 'Basecamp',
            'duration_minutes' => 120,
            'minimum_participants' => 1,
            'maximum_participants' => 10,
            'price_per_person' => 100000,
            'status' => TourPackageStatus::ACTIVE,
        ]);
        $booking = Booking::query()->create([
            'booking_code' => 'WEB-LIFE-001',
            'customer_id' => $customer->id,
            'tour_package_id' => $package->id,
            'tour_date' => now()->addWeek()->toDateString(),
            'participant_count' => 1,
            'total_amount' => 100000,
            'status' => BookingStatus::ONGOING,
            'payment_status' => PaymentStatus::PAID,
        ]);
        $participant = BookingParticipant::query()->create([
            'booking_id' => $booking->id,
            'user_id' => $customer->id,
            'name' => 'Peserta Web',
            'phone' => '08123456789',
            'is_group_leader' => true,
        ]);
        $assignment = DriverAssignment::query()->create([
            'booking_id' => $booking->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'offered_by' => $admin->id,
            'status' => DriverAssignmentStatus::ACCEPTED,
            'offered_at' => now(),
            'responded_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put("/admin/bookings/{$booking->id}/participant-allocations", [
                'booking_participant_id' => $participant->id,
                'driver_assignment_id' => $assignment->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('booking_participant_vehicle_allocations', [
            'booking_id' => $booking->id,
            'booking_participant_id' => $participant->id,
            'driver_assignment_id' => $assignment->id,
        ]);

        $this->actingAs($admin)
            ->patch("/admin/bookings/{$booking->id}/status", [
                'status' => BookingStatus::COMPLETED->value,
            ])
            ->assertRedirect();

        $this->assertSame(BookingStatus::COMPLETED, $booking->fresh()->status);
        $this->assertSame(100, $profile->fresh()->available_points);
        $this->assertDatabaseCount('point_ledgers', 1);
    }
}
