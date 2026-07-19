<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverStatus;
use App\Enums\PaymentStatus;
use App\Enums\TourPackageStatus;
use App\Enums\VehicleOwnershipType;
use App\Enums\VehicleStatus;
use App\Enums\VerificationStatus;
use App\Models\Booking;
use App\Models\DriverAssignment;
use App\Models\DriverProfile;
use App\Models\TourPackage;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DriverAssignmentResponseFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_accept_owned_offered_assignment(): void
    {
        [$driver, $profile, $vehicle] = $this->createApprovedAvailableDriverWithVehicle();
        $booking = $this->createBooking(now()->addMonth()->toDateString());
        $assignment = $this->createAssignment($booking, $driver, $vehicle);

        Sanctum::actingAs($driver);

        $this->patchJson("/api/v1/driver/assignments/{$assignment->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', DriverAssignmentStatus::ACCEPTED->value);

        $assignment->refresh();
        $this->assertSame(DriverAssignmentStatus::ACCEPTED, $assignment->status);
        $this->assertNotNull($assignment->responded_at);
        $this->assertNull($assignment->rejection_reason);
    }

    public function test_driver_can_reject_owned_assignment_with_reason(): void
    {
        [$driver, , $vehicle] = $this->createApprovedAvailableDriverWithVehicle();
        $booking = $this->createBooking(now()->addMonth()->toDateString());
        $assignment = $this->createAssignment($booking, $driver, $vehicle);

        Sanctum::actingAs($driver);

        $this->patchJson("/api/v1/driver/assignments/{$assignment->id}/reject", [
            'rejection_reason' => 'Kendaraan perlu perawatan.',
        ])->assertOk()
            ->assertJsonPath('data.status', DriverAssignmentStatus::REJECTED->value)
            ->assertJsonPath('data.rejection_reason', 'Kendaraan perlu perawatan.');

        $this->assertSame(DriverAssignmentStatus::REJECTED, $assignment->fresh()->status);
    }

    public function test_rejection_requires_reason_and_repeated_response_is_rejected(): void
    {
        [$driver, , $vehicle] = $this->createApprovedAvailableDriverWithVehicle();
        $booking = $this->createBooking(now()->addMonth()->toDateString());
        $assignment = $this->createAssignment($booking, $driver, $vehicle);

        Sanctum::actingAs($driver);

        $this->patchJson("/api/v1/driver/assignments/{$assignment->id}/reject", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('rejection_reason');

        $this->patchJson("/api/v1/driver/assignments/{$assignment->id}/accept")
            ->assertOk();

        $this->patchJson("/api/v1/driver/assignments/{$assignment->id}/reject", [
            'rejection_reason' => 'Tidak boleh diproses ulang.',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('assignment');

        $this->assertSame(DriverAssignmentStatus::ACCEPTED, $assignment->fresh()->status);
    }

    public function test_driver_cannot_access_or_respond_to_another_driver_assignment(): void
    {
        [$owner, , $vehicle] = $this->createApprovedAvailableDriverWithVehicle();
        [$otherDriver] = $this->createApprovedAvailableDriverWithVehicle();
        $booking = $this->createBooking(now()->addMonth()->toDateString());
        $assignment = $this->createAssignment($booking, $owner, $vehicle);

        Sanctum::actingAs($otherDriver);

        $this->getJson("/api/v1/driver/assignments/{$assignment->id}")->assertNotFound();
        $this->patchJson("/api/v1/driver/assignments/{$assignment->id}/accept")->assertNotFound();

        $this->assertSame(DriverAssignmentStatus::OFFERED, $assignment->fresh()->status);
    }

    public function test_same_driver_cannot_accept_two_bookings_on_same_date(): void
    {
        [$driver, , $vehicleOne] = $this->createApprovedAvailableDriverWithVehicle();
        $vehicleTwo = $this->createVehicle();
        $tourDate = now()->addMonth()->toDateString();
        $first = $this->createAssignment($this->createBooking($tourDate), $driver, $vehicleOne, DriverAssignmentStatus::ACCEPTED);
        $second = $this->createAssignment($this->createBooking($tourDate), $driver, $vehicleTwo);

        Sanctum::actingAs($driver);

        $this->patchJson("/api/v1/driver/assignments/{$second->id}/accept")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('assignment');

        $this->assertSame(DriverAssignmentStatus::ACCEPTED, $first->fresh()->status);
        $this->assertSame(DriverAssignmentStatus::OFFERED, $second->fresh()->status);
    }

    public function test_vehicle_conflict_is_rejected_but_different_date_is_allowed(): void
    {
        [$firstDriver, , $sharedVehicle] = $this->createApprovedAvailableDriverWithVehicle();
        [$secondDriver] = $this->createApprovedAvailableDriverWithVehicle();
        $tourDate = now()->addMonth()->toDateString();

        $this->createAssignment($this->createBooking($tourDate), $firstDriver, $sharedVehicle, DriverAssignmentStatus::ACCEPTED);
        $sameDate = $this->createAssignment($this->createBooking($tourDate), $secondDriver, $sharedVehicle);

        Sanctum::actingAs($secondDriver);
        $this->patchJson("/api/v1/driver/assignments/{$sameDate->id}/accept")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('assignment');

        $differentDate = $this->createAssignment(
            $this->createBooking(now()->addMonths(2)->toDateString()),
            $secondDriver,
            $sharedVehicle,
        );

        $this->patchJson("/api/v1/driver/assignments/{$differentDate->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', DriverAssignmentStatus::ACCEPTED->value);
    }

    private function createApprovedAvailableDriverWithVehicle(): array
    {
        $driver = User::factory()->driver()->create();
        $profile = DriverProfile::query()->create([
            'user_id' => $driver->id,
            'status' => DriverStatus::AVAILABLE,
            'verification_status' => VerificationStatus::APPROVED,
            'license_number' => fake()->unique()->bothify('SIM-########'),
            'identity_number' => fake()->unique()->numerify('################'),
            'joined_at' => now()->toDateString(),
        ]);
        $vehicle = $this->createVehicle($profile);

        return [$driver, $profile, $vehicle];
    }

    private function createVehicle(?DriverProfile $profile = null): Vehicle
    {
        return Vehicle::query()->create([
            'driver_profile_id' => $profile?->id,
            'ownership_type' => $profile ? VehicleOwnershipType::DRIVER : VehicleOwnershipType::COMPANY,
            'name' => 'Jeep Assignment Test',
            'plate_number' => fake()->unique()->bothify('AB #### ??'),
            'brand' => 'Jeep',
            'model' => 'CJ7',
            'year' => 2020,
            'capacity' => 4,
            'status' => VehicleStatus::AVAILABLE,
            'verification_status' => VerificationStatus::APPROVED,
        ]);
    }

    private function createBooking(string $tourDate): Booking
    {
        $customer = User::factory()->customer()->create();
        $package = TourPackage::query()->create([
            'name' => 'Paket Assignment Test',
            'slug' => 'assignment-test-'.fake()->unique()->numberBetween(1, 999999),
            'minimum_participants' => 1,
            'maximum_participants' => 10,
            'price_per_person' => 100000,
            'status' => TourPackageStatus::ACTIVE,
        ]);

        return Booking::query()->create([
            'booking_code' => 'ASG-'.fake()->unique()->numerify('######'),
            'customer_id' => $customer->id,
            'tour_package_id' => $package->id,
            'tour_date' => $tourDate,
            'participant_count' => 1,
            'total_amount' => 100000,
            'status' => BookingStatus::CONFIRMED,
            'payment_status' => PaymentStatus::PAID,
        ]);
    }

    private function createAssignment(
        Booking $booking,
        User $driver,
        Vehicle $vehicle,
        DriverAssignmentStatus $status = DriverAssignmentStatus::OFFERED,
    ): DriverAssignment {
        return DriverAssignment::query()->create([
            'booking_id' => $booking->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => $status,
            'offered_at' => now(),
            'responded_at' => $status === DriverAssignmentStatus::OFFERED ? null : now(),
        ]);
    }
}
