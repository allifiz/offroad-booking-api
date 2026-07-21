<?php

namespace Tests\Feature;

use App\Enums\VehicleOwnershipType;
use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWebVehicleFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_vehicles_from_web(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/vehicles')
            ->assertOk()
            ->assertSee('Kendaraan');

        $response = $this->actingAs($admin)->post('/admin/vehicles', [
            'driver_profile_id' => null,
            'ownership_type' => VehicleOwnershipType::COMPANY->value,
            'name' => 'Jeep Operasional',
            'plate_number' => 'B 1234 XYZ',
            'brand' => 'Jeep',
            'model' => 'Wrangler',
            'year' => 2024,
            'capacity' => 4,
            'status' => VehicleStatus::AVAILABLE->value,
            'notes' => 'Armada utama.',
        ]);

        $vehicle = Vehicle::query()->where('plate_number', 'B 1234 XYZ')->firstOrFail();
        $response->assertRedirect(route('admin.vehicles.edit', $vehicle));

        $this->actingAs($admin)
            ->put("/admin/vehicles/{$vehicle->id}", [
                'driver_profile_id' => null,
                'ownership_type' => VehicleOwnershipType::COMPANY->value,
                'name' => 'Jeep Operasional Premium',
                'plate_number' => 'B 1234 XYZ',
                'brand' => 'Jeep',
                'model' => 'Wrangler',
                'year' => 2024,
                'capacity' => 5,
                'status' => VehicleStatus::MAINTENANCE->value,
                'notes' => 'Jadwal servis.',
            ])
            ->assertRedirect(route('admin.vehicles.edit', $vehicle));

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'name' => 'Jeep Operasional Premium',
            'capacity' => 5,
            'status' => VehicleStatus::MAINTENANCE->value,
        ]);

        $this->actingAs($admin)
            ->delete("/admin/vehicles/{$vehicle->id}")
            ->assertRedirect(route('admin.vehicles.index'));

        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
    }

    public function test_non_admin_cannot_manage_vehicles(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get('/admin/vehicles')->assertForbidden();
    }
}
