<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\TourPackageStatus;
use App\Models\Booking;
use App\Models\TourPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWebBookingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_view_and_confirm_paid_booking(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        $package = TourPackage::query()->create([
            'name' => 'Paket Web Booking',
            'slug' => 'paket-web-booking',
            'description' => 'Test',
            'meeting_point' => 'Basecamp',
            'duration_minutes' => 120,
            'minimum_participants' => 1,
            'maximum_participants' => 10,
            'price_per_person' => 100000,
            'status' => TourPackageStatus::ACTIVE,
        ]);
        $booking = Booking::query()->create([
            'booking_code' => 'WEB-BKG-001',
            'customer_id' => $customer->id,
            'tour_package_id' => $package->id,
            'tour_date' => now()->addWeek()->toDateString(),
            'participant_count' => 2,
            'total_amount' => 200000,
            'status' => BookingStatus::PENDING,
            'payment_status' => PaymentStatus::PAID,
        ]);

        $this->actingAs($admin)
            ->get('/admin/bookings')
            ->assertOk()
            ->assertSee('WEB-BKG-001');

        $this->actingAs($admin)
            ->get("/admin/bookings/{$booking->id}")
            ->assertOk()
            ->assertSee('Booking detail');

        $this->actingAs($admin)
            ->patch("/admin/bookings/{$booking->id}/status", ['status' => BookingStatus::CONFIRMED->value])
            ->assertRedirect();

        $this->assertSame(BookingStatus::CONFIRMED, $booking->fresh()->status);
    }

    public function test_non_admin_cannot_access_booking_operations(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get('/admin/bookings')->assertForbidden();
    }
}
