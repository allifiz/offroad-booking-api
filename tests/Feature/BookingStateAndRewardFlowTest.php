<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\DriverAssignmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\PointLedgerType;
use App\Enums\TourPackageStatus;
use App\Models\Booking;
use App\Models\DriverAssignment;
use App\Models\DriverProfile;
use App\Models\PointLedger;
use App\Models\TourPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingStateAndRewardFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_cannot_skip_from_pending_to_completed(): void
    {
        $admin = User::factory()->admin()->create();
        $booking = $this->createBooking(BookingStatus::PENDING, PaymentStatus::PAID);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/admin/bookings/{$booking->id}/status", [
            'status' => BookingStatus::COMPLETED->value,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => BookingStatus::PENDING->value,
        ]);
    }

    public function test_unpaid_booking_cannot_be_confirmed(): void
    {
        $admin = User::factory()->admin()->create();
        $booking = $this->createBooking(BookingStatus::PENDING, PaymentStatus::UNPAID);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/admin/bookings/{$booking->id}/status", [
            'status' => BookingStatus::CONFIRMED->value,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('payment_status');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => BookingStatus::PENDING->value,
        ]);
    }

    public function test_confirmed_booking_requires_an_accepted_assignment_before_ongoing(): void
    {
        $admin = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create();
        $booking = $this->createBooking(BookingStatus::CONFIRMED, PaymentStatus::PAID);

        DriverAssignment::query()->create([
            'booking_id' => $booking->id,
            'driver_id' => $driver->id,
            'offered_by' => $admin->id,
            'status' => DriverAssignmentStatus::OFFERED,
            'offered_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/admin/bookings/{$booking->id}/status", [
            'status' => BookingStatus::ONGOING->value,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => BookingStatus::CONFIRMED->value,
        ]);
    }

    public function test_completing_booking_credits_each_accepted_driver_once(): void
    {
        config()->set('offroad.points_per_completed_trip', 100);

        $admin = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create();
        $profile = DriverProfile::query()->create([
            'user_id' => $driver->id,
            'available_points' => 0,
            'held_points' => 0,
        ]);
        $booking = $this->createBooking(BookingStatus::ONGOING, PaymentStatus::PAID);

        DriverAssignment::query()->create([
            'booking_id' => $booking->id,
            'driver_id' => $driver->id,
            'offered_by' => $admin->id,
            'status' => DriverAssignmentStatus::ACCEPTED,
            'offered_at' => now()->subHour(),
            'responded_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/admin/bookings/{$booking->id}/status", [
            'status' => BookingStatus::COMPLETED->value,
        ])->assertOk()
            ->assertJsonPath('data.status', BookingStatus::COMPLETED->value);

        $this->assertSame(100, $profile->refresh()->available_points);
        $this->assertDatabaseCount('point_ledgers', 1);
        $this->assertDatabaseHas('point_ledgers', [
            'driver_profile_id' => $profile->id,
            'type' => PointLedgerType::CREDIT->value,
            'points' => 100,
            'reference_type' => Booking::class,
            'reference_id' => $booking->id,
            'available_balance_after' => 100,
            'held_balance_after' => 0,
        ]);

        $this->patchJson("/api/v1/admin/bookings/{$booking->id}/status", [
            'status' => BookingStatus::COMPLETED->value,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertSame(100, $profile->refresh()->available_points);
        $this->assertDatabaseCount('point_ledgers', 1);
    }

    public function test_existing_booking_credit_prevents_duplicate_reward(): void
    {
        config()->set('offroad.points_per_completed_trip', 100);

        $admin = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create();
        $profile = DriverProfile::query()->create([
            'user_id' => $driver->id,
            'available_points' => 100,
            'held_points' => 0,
        ]);
        $booking = $this->createBooking(BookingStatus::ONGOING, PaymentStatus::PAID);

        DriverAssignment::query()->create([
            'booking_id' => $booking->id,
            'driver_id' => $driver->id,
            'offered_by' => $admin->id,
            'status' => DriverAssignmentStatus::ACCEPTED,
            'offered_at' => now()->subHour(),
            'responded_at' => now(),
        ]);

        PointLedger::query()->create([
            'driver_profile_id' => $profile->id,
            'type' => PointLedgerType::CREDIT,
            'points' => 100,
            'available_balance_after' => 100,
            'held_balance_after' => 0,
            'reference_type' => Booking::class,
            'reference_id' => $booking->id,
            'description' => 'Existing trip reward.',
            'occurred_at' => now()->subMinute(),
        ]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/admin/bookings/{$booking->id}/status", [
            'status' => BookingStatus::COMPLETED->value,
        ])->assertOk();

        $this->assertSame(100, $profile->refresh()->available_points);
        $this->assertSame(1, PointLedger::query()
            ->where('driver_profile_id', $profile->id)
            ->where('type', PointLedgerType::CREDIT->value)
            ->where('reference_type', Booking::class)
            ->where('reference_id', $booking->id)
            ->count());
    }

    private function createBooking(BookingStatus $status, PaymentStatus $paymentStatus): Booking
    {
        $customer = User::factory()->customer()->create();
        $package = TourPackage::query()->create([
            'name' => fake()->unique()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'minimum_participants' => 1,
            'maximum_participants' => 10,
            'price_per_person' => 250000,
            'status' => TourPackageStatus::ACTIVE,
        ]);

        return Booking::query()->create([
            'booking_code' => 'TEST-'.fake()->unique()->numerify('########'),
            'customer_id' => $customer->id,
            'tour_package_id' => $package->id,
            'tour_date' => now()->addMonth()->toDateString(),
            'participant_count' => 1,
            'total_amount' => 250000,
            'status' => $status,
            'payment_status' => $paymentStatus,
        ]);
    }
}
