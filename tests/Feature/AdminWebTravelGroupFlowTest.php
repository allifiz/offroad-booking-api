<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\TourPackageStatus;
use App\Enums\TravelGroupSource;
use App\Enums\TravelGroupStatus;
use App\Models\Booking;
use App\Models\TourPackage;
use App\Models\TravelGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWebTravelGroupFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_view_update_and_attach_booking_to_travel_group(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($admin)->get('/admin/travel-groups')->assertOk()->assertSee('Travel Groups');

        $response = $this->actingAs($admin)->post('/admin/travel-groups', [
            'name' => 'Rombongan Sunrise',
            'source' => TravelGroupSource::WEBSITE->value,
            'leader_user_id' => $customer->id,
            'member_limit' => 10,
            'member_user_ids' => [$customer->id],
            'notes' => 'Group pengujian.',
        ]);

        $group = TravelGroup::query()->where('name', 'Rombongan Sunrise')->firstOrFail();
        $response->assertRedirect(route('admin.travel-groups.show', $group));
        $this->assertDatabaseHas('travel_group_members', ['travel_group_id' => $group->id, 'user_id' => $customer->id, 'is_leader' => true]);

        $package = TourPackage::query()->create([
            'name' => 'Paket Group',
            'slug' => 'paket-group',
            'minimum_participants' => 1,
            'maximum_participants' => 20,
            'price_per_person' => 100000,
            'status' => TourPackageStatus::ACTIVE,
        ]);
        $booking = Booking::query()->create([
            'booking_code' => 'GROUP-BKG-001',
            'customer_id' => $customer->id,
            'tour_package_id' => $package->id,
            'tour_date' => now()->addWeek()->toDateString(),
            'participant_count' => 2,
            'total_amount' => 200000,
            'status' => BookingStatus::PENDING,
            'payment_status' => PaymentStatus::UNPAID,
        ]);

        $this->actingAs($admin)
            ->post("/admin/travel-groups/{$group->id}/bookings", ['booking_id' => $booking->id])
            ->assertRedirect();
        $this->assertSame($group->id, $booking->fresh()->travel_group_id);

        $this->actingAs($admin)
            ->patch("/admin/travel-groups/{$group->id}/status", ['status' => TravelGroupStatus::OPEN->value])
            ->assertRedirect();
        $this->assertSame(TravelGroupStatus::OPEN, $group->fresh()->status);

        $this->actingAs($admin)->get("/admin/travel-groups/{$group->id}")->assertOk()->assertSee('GROUP-BKG-001');
    }

    public function test_non_admin_cannot_manage_travel_groups(): void
    {
        $customer = User::factory()->customer()->create();
        $this->actingAs($customer)->get('/admin/travel-groups')->assertForbidden();
    }
}
