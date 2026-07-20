<?php

namespace Tests\Feature;

use App\Enums\DriverStatus;
use App\Enums\VehicleOwnershipType;
use App\Enums\VehicleStatus;
use App\Enums\VerificationStatus;
use App\Models\DriverProfile;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWebDriverVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_review_and_approve_driver_and_vehicle(): void
    {
        $admin = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create();
        $profile = DriverProfile::query()->create([
            'user_id' => $driver->id,
            'status' => DriverStatus::UNAVAILABLE,
            'verification_status' => VerificationStatus::PENDING,
            'license_number' => 'SIM-WEB-001',
            'identity_number' => 'ID-WEB-001',
            'address' => 'Basecamp Test',
            'date_of_birth' => '1995-01-01',
            'available_points' => 0,
            'held_points' => 0,
        ]);
        $vehicle = Vehicle::query()->create([
            'driver_profile_id' => $profile->id,
            'ownership_type' => VehicleOwnershipType::DRIVER,
            'name' => 'Jeep Verification',
            'plate_number' => 'B1234WEB',
            'brand' => 'Jeep',
            'model' => 'CJ7',
            'year' => 2020,
            'capacity' => 4,
            'status' => VehicleStatus::UNAVAILABLE,
            'verification_status' => VerificationStatus::PENDING,
        ]);

        $this->actingAs($admin)->get('/admin/drivers')
            ->assertOk()
            ->assertSee($driver->name)
            ->assertSee('SIM-WEB-001');

        $this->actingAs($admin)->get("/admin/drivers/{$profile->id}")
            ->assertOk()
            ->assertSee('Driver detail')
            ->assertSee('Jeep Verification');

        $this->actingAs($admin)->patch("/admin/drivers/{$profile->id}", [
            'verification_status' => VerificationStatus::APPROVED->value,
        ])->assertRedirect();

        $this->actingAs($admin)->patch("/admin/drivers/{$profile->id}/vehicles/{$vehicle->id}", [
            'verification_status' => VerificationStatus::APPROVED->value,
        ])->assertRedirect();

        $this->assertSame(VerificationStatus::APPROVED, $profile->fresh()->verification_status);
        $this->assertSame(DriverStatus::AVAILABLE, $profile->fresh()->status);
        $this->assertSame(VerificationStatus::APPROVED, $vehicle->fresh()->verification_status);
        $this->assertSame(VehicleStatus::AVAILABLE, $vehicle->fresh()->status);
        $this->assertSame($admin->id, $profile->fresh()->verified_by);
        $this->assertSame($admin->id, $vehicle->fresh()->verified_by);
    }

    public function test_rejection_requires_reason_and_non_admin_is_forbidden(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        $driver = User::factory()->driver()->create();
        $profile = DriverProfile::query()->create([
            'user_id' => $driver->id,
            'status' => DriverStatus::UNAVAILABLE,
            'verification_status' => VerificationStatus::PENDING,
            'available_points' => 0,
            'held_points' => 0,
        ]);

        $this->actingAs($admin)->patch("/admin/drivers/{$profile->id}", [
            'verification_status' => VerificationStatus::REJECTED->value,
        ])->assertSessionHasErrors('rejection_reason');

        $this->actingAs($customer)->get('/admin/drivers')->assertForbidden();
    }
}
