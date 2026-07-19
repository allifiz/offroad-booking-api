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

class DriverVehicleCrudFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_create_owned_vehicle_in_pending_unavailable_state(): void
    {
        [$driver, $profile] = $this->createDriver();
        Sanctum::actingAs($driver);

        $response = $this->postJson('/api/v1/driver/vehicles', [
            'name' => 'Jeep Driver Test',
            'plate_number' => 'B 1001 TST',
            'brand' => 'Jeep',
            'model' => 'Wrangler',
            'year' => 2022,
            'capacity' => 4,
            'notes' => 'Kendaraan test driver.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.driver_profile_id', $profile->id)
            ->assertJsonPath('data.ownership_type', VehicleOwnershipType::DRIVER->value)
            ->assertJsonPath('data.status', VehicleStatus::UNAVAILABLE->value)
            ->assertJsonPath('data.verification_status', VerificationStatus::PENDING->value);

        $this->assertDatabaseHas('vehicles', [
            'driver_profile_id' => $profile->id,
            'plate_number' => 'B 1001 TST',
            'ownership_type' => VehicleOwnershipType::DRIVER->value,
            'status' => VehicleStatus::UNAVAILABLE->value,
            'verification_status' => VerificationStatus::PENDING->value,
        ]);
    }

    public function test_notes_only_update_keeps_existing_verification_state(): void
    {
        [$driver, $profile] = $this->createDriver();
        $admin = User::factory()->admin()->create();
        $vehicle = $this->createVehicle($profile, [
            'status' => VehicleStatus::AVAILABLE,
            'verification_status' => VerificationStatus::APPROVED,
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);

        Sanctum::actingAs($driver);

        $this->patchJson("/api/v1/driver/vehicles/{$vehicle->id}", [
            'notes' => 'Servis rutin selesai.',
        ])->assertOk()
            ->assertJsonPath('data.notes', 'Servis rutin selesai.')
            ->assertJsonPath('data.status', VehicleStatus::AVAILABLE->value)
            ->assertJsonPath('data.verification_status', VerificationStatus::APPROVED->value);

        $vehicle->refresh();
        $this->assertSame(VehicleStatus::AVAILABLE, $vehicle->status);
        $this->assertSame(VerificationStatus::APPROVED, $vehicle->verification_status);
        $this->assertSame($admin->id, $vehicle->verified_by);
        $this->assertNotNull($vehicle->verified_at);
    }

    public function test_sensitive_update_resets_vehicle_to_pending_and_unavailable(): void
    {
        [$driver, $profile] = $this->createDriver();
        $admin = User::factory()->admin()->create();
        $vehicle = $this->createVehicle($profile, [
            'status' => VehicleStatus::AVAILABLE,
            'verification_status' => VerificationStatus::APPROVED,
            'rejection_reason' => 'Alasan lama',
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);

        Sanctum::actingAs($driver);

        $this->patchJson("/api/v1/driver/vehicles/{$vehicle->id}", [
            'capacity' => 5,
            'plate_number' => 'B 1002 TST',
        ])->assertOk()
            ->assertJsonPath('data.capacity', 5)
            ->assertJsonPath('data.plate_number', 'B 1002 TST')
            ->assertJsonPath('data.status', VehicleStatus::UNAVAILABLE->value)
            ->assertJsonPath('data.verification_status', VerificationStatus::PENDING->value)
            ->assertJsonPath('data.rejection_reason', null)
            ->assertJsonPath('data.verified_by', null)
            ->assertJsonPath('data.verified_at', null);
    }

    public function test_driver_cannot_access_update_or_delete_another_driver_vehicle(): void
    {
        [$owner, $ownerProfile] = $this->createDriver();
        [$otherDriver] = $this->createDriver();
        $vehicle = $this->createVehicle($ownerProfile);

        Sanctum::actingAs($otherDriver);

        $this->getJson("/api/v1/driver/vehicles/{$vehicle->id}")->assertNotFound();
        $this->patchJson("/api/v1/driver/vehicles/{$vehicle->id}", [
            'notes' => 'Tidak boleh.',
        ])->assertNotFound();
        $this->deleteJson("/api/v1/driver/vehicles/{$vehicle->id}")->assertNotFound();

        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id]);
    }

    public function test_plate_number_must_be_unique_on_create_and_update(): void
    {
        [$driver, $profile] = $this->createDriver();
        $existing = $this->createVehicle($profile, ['plate_number' => 'B 1003 TST']);
        $other = $this->createVehicle($profile, ['plate_number' => 'B 1004 TST']);

        Sanctum::actingAs($driver);

        $this->postJson('/api/v1/driver/vehicles', [
            'name' => 'Duplicate Plate',
            'plate_number' => $existing->plate_number,
            'capacity' => 4,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('plate_number');

        $this->patchJson("/api/v1/driver/vehicles/{$other->id}", [
            'plate_number' => $existing->plate_number,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('plate_number');
    }

    public function test_driver_can_delete_vehicle_without_active_assignment(): void
    {
        [$driver, $profile] = $this->createDriver();
        $vehicle = $this->createVehicle($profile);

        Sanctum::actingAs($driver);

        $this->deleteJson("/api/v1/driver/vehicles/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
    }

    public function test_vehicle_with_offered_or_accepted_assignment_cannot_be_deleted(): void
    {
        [$driver, $profile] = $this->createDriver();
        $vehicle = $this->createVehicle($profile);
        $booking = $this->createBooking();

        DriverAssignment::query()->create([
            'booking_id' => $booking->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => DriverAssignmentStatus::OFFERED,
            'offered_at' => now(),
        ]);

        Sanctum::actingAs($driver);

        $this->deleteJson("/api/v1/driver/vehicles/{$vehicle->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('vehicle');

        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id]);
    }

    private function createDriver(): array
    {
        $driver = User::factory()->driver()->create();
        $profile = DriverProfile::query()->create([
            'user_id' => $driver->id,
            'status' => DriverStatus::AVAILABLE,
            'verification_status' => VerificationStatus::APPROVED,
            'available_points' => 0,
            'held_points' => 0,
        ]);

        return [$driver, $profile];
    }

    private function createVehicle(DriverProfile $profile, array $overrides = []): Vehicle
    {
        return Vehicle::query()->create([
            'driver_profile_id' => $profile->id,
            'ownership_type' => VehicleOwnershipType::DRIVER,
            'name' => 'Jeep Test '.fake()->unique()->numberBetween(1, 999999),
            'plate_number' => 'B '.fake()->unique()->numerify('####').' TST',
            'brand' => 'Jeep',
            'model' => 'Wrangler',
            'year' => 2021,
            'capacity' => 4,
            'status' => VehicleStatus::UNAVAILABLE,
            'verification_status' => VerificationStatus::PENDING,
            'notes' => null,
            ...$overrides,
        ]);
    }

    private function createBooking(): Booking
    {
        $customer = User::factory()->customer()->create();
        $package = TourPackage::query()->create([
            'name' => 'Paket Vehicle Test',
            'slug' => 'paket-vehicle-test-'.fake()->unique()->numberBetween(1, 999999),
            'description' => 'Paket untuk test kendaraan driver.',
            'meeting_point' => 'Basecamp',
            'duration_minutes' => 120,
            'minimum_participants' => 1,
            'maximum_participants' => 10,
            'price_per_person' => 100000,
            'status' => TourPackageStatus::ACTIVE,
        ]);

        return Booking::query()->create([
            'booking_code' => 'VEH-'.fake()->unique()->numerify('######'),
            'customer_id' => $customer->id,
            'tour_package_id' => $package->id,
            'tour_date' => now()->addMonth()->toDateString(),
            'participant_count' => 1,
            'total_amount' => 100000,
            'status' => BookingStatus::CONFIRMED,
            'payment_status' => PaymentStatus::PAID,
        ]);
    }
}
