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
use App\Models\BookingParticipantVehicleAllocation;
use App\Models\DriverAssignment;
use App\Models\DriverProfile;
use App\Models\TourPackage;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ParticipantAllocationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_allocate_participant_to_accepted_assignment(): void
    {
        [$admin, $booking, $participants, $assignment] = $this->createScenario(capacity: 2);

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/v1/admin/bookings/{$booking->id}/participant-allocations", [
            'booking_participant_id' => $participants[0]->id,
            'driver_assignment_id' => $assignment->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.booking_participant_id', $participants[0]->id)
            ->assertJsonPath('data.driver_assignment_id', $assignment->id);

        $this->assertDatabaseHas('booking_participant_vehicle_allocations', [
            'booking_id' => $booking->id,
            'booking_participant_id' => $participants[0]->id,
            'driver_assignment_id' => $assignment->id,
        ]);
    }

    public function test_only_accepted_assignment_can_receive_participants(): void
    {
        [$admin, $booking, $participants, $assignment] = $this->createScenario(
            assignmentStatus: DriverAssignmentStatus::OFFERED,
        );

        Sanctum::actingAs($admin);

        $this->putJson("/api/v1/admin/bookings/{$booking->id}/participant-allocations", [
            'booking_participant_id' => $participants[0]->id,
            'driver_assignment_id' => $assignment->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('driver_assignment_id');

        $this->assertDatabaseCount('booking_participant_vehicle_allocations', 0);
    }

    public function test_vehicle_capacity_cannot_be_exceeded(): void
    {
        [$admin, $booking, $participants, $assignment] = $this->createScenario(capacity: 1);

        Sanctum::actingAs($admin);

        $this->allocate($booking, $participants[0], $assignment)->assertOk();

        $this->allocate($booking, $participants[1], $assignment)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('driver_assignment_id');

        $this->assertDatabaseCount('booking_participant_vehicle_allocations', 1);
        $this->assertDatabaseMissing('booking_participant_vehicle_allocations', [
            'booking_participant_id' => $participants[1]->id,
        ]);
    }

    public function test_participant_can_be_moved_to_another_accepted_assignment_without_duplicate_allocation(): void
    {
        [$admin, $booking, $participants, $firstAssignment] = $this->createScenario(capacity: 2);
        $secondAssignment = $this->createAcceptedAssignment($booking, capacity: 2);

        Sanctum::actingAs($admin);

        $this->allocate($booking, $participants[0], $firstAssignment)->assertOk();
        $this->allocate($booking, $participants[0], $secondAssignment)->assertOk();

        $this->assertDatabaseCount('booking_participant_vehicle_allocations', 1);
        $this->assertDatabaseHas('booking_participant_vehicle_allocations', [
            'booking_id' => $booking->id,
            'booking_participant_id' => $participants[0]->id,
            'driver_assignment_id' => $secondAssignment->id,
        ]);
    }

    public function test_participant_and_assignment_from_other_booking_return_not_found(): void
    {
        [$admin, $booking, $participants, $assignment] = $this->createScenario();
        [, $otherBooking, $otherParticipants, $otherAssignment] = $this->createScenario();

        Sanctum::actingAs($admin);

        $this->putJson("/api/v1/admin/bookings/{$booking->id}/participant-allocations", [
            'booking_participant_id' => $otherParticipants[0]->id,
            'driver_assignment_id' => $assignment->id,
        ])->assertNotFound();

        $this->putJson("/api/v1/admin/bookings/{$booking->id}/participant-allocations", [
            'booking_participant_id' => $participants[0]->id,
            'driver_assignment_id' => $otherAssignment->id,
        ])->assertNotFound();

        $this->assertDatabaseCount('booking_participant_vehicle_allocations', 0);
    }

    public function test_final_booking_cannot_be_allocated_and_allocation_list_reports_unallocated_participants(): void
    {
        [$admin, $booking, $participants, $assignment] = $this->createScenario();

        Sanctum::actingAs($admin);

        $this->allocate($booking, $participants[0], $assignment)->assertOk();

        $this->getJson("/api/v1/admin/bookings/{$booking->id}/participant-allocations")
            ->assertOk()
            ->assertJsonCount(1, 'data.allocations')
            ->assertJsonCount(1, 'data.unallocated_participants')
            ->assertJsonPath('data.unallocated_participants.0.id', $participants[1]->id);

        $booking->update(['status' => BookingStatus::COMPLETED]);

        $this->allocate($booking, $participants[1], $assignment)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('booking');

        $this->assertDatabaseCount('booking_participant_vehicle_allocations', 1);
    }

    private function allocate(Booking $booking, BookingParticipant $participant, DriverAssignment $assignment)
    {
        return $this->putJson("/api/v1/admin/bookings/{$booking->id}/participant-allocations", [
            'booking_participant_id' => $participant->id,
            'driver_assignment_id' => $assignment->id,
        ]);
    }

    private function createScenario(
        int $capacity = 2,
        DriverAssignmentStatus $assignmentStatus = DriverAssignmentStatus::ACCEPTED,
    ): array {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        $package = TourPackage::query()->create([
            'name' => 'Paket Allocation Test',
            'slug' => 'allocation-'.fake()->unique()->numerify('######'),
            'description' => 'Paket feature test allocation.',
            'meeting_point' => 'Basecamp',
            'duration_minutes' => 120,
            'minimum_participants' => 1,
            'maximum_participants' => 10,
            'price_per_person' => 100000,
            'status' => TourPackageStatus::ACTIVE,
        ]);
        $booking = Booking::query()->create([
            'booking_code' => 'ALLOC-'.fake()->unique()->numerify('######'),
            'customer_id' => $customer->id,
            'tour_package_id' => $package->id,
            'tour_date' => now()->addMonth()->toDateString(),
            'participant_count' => 2,
            'total_amount' => 200000,
            'status' => BookingStatus::CONFIRMED,
            'payment_status' => PaymentStatus::PAID,
            'notes' => 'Participant allocation test.',
        ]);
        $participants = collect([
            BookingParticipant::query()->create([
                'booking_id' => $booking->id,
                'user_id' => $customer->id,
                'name' => 'Leader Allocation',
                'phone' => '081111111111',
                'is_group_leader' => true,
            ]),
            BookingParticipant::query()->create([
                'booking_id' => $booking->id,
                'user_id' => null,
                'name' => 'Member Allocation',
                'phone' => '082222222222',
                'is_group_leader' => false,
            ]),
        ]);
        $assignment = $this->createAcceptedAssignment($booking, $capacity, $assignmentStatus);

        return [$admin, $booking, $participants, $assignment];
    }

    private function createAcceptedAssignment(
        Booking $booking,
        int $capacity = 2,
        DriverAssignmentStatus $status = DriverAssignmentStatus::ACCEPTED,
    ): DriverAssignment {
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
            'name' => 'Jeep Allocation',
            'plate_number' => 'B'.fake()->unique()->numerify('####').'XYZ',
            'brand' => 'Jeep',
            'model' => 'CJ7',
            'year' => 2020,
            'capacity' => $capacity,
            'status' => VehicleStatus::AVAILABLE,
            'verification_status' => VerificationStatus::APPROVED,
        ]);

        return DriverAssignment::query()->create([
            'booking_id' => $booking->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'offered_by' => User::factory()->admin()->create()->id,
            'status' => $status,
            'offered_at' => now(),
            'responded_at' => $status === DriverAssignmentStatus::ACCEPTED ? now() : null,
        ]);
    }
}
