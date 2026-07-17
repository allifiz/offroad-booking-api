<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_vehicles(): void
    {
        $this->getJson('/api/v1/admin/vehicles')->assertUnauthorized();
    }

    public function test_non_admin_cannot_manage_vehicles(): void
    {
        Sanctum::actingAs(User::factory()->customer()->create());

        $this->getJson('/api/v1/admin/vehicles')->assertForbidden();
    }

    public function test_admin_can_create_update_and_delete_vehicle(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $response = $this->postJson('/api/v1/admin/vehicles', [
            'name' => 'Jeep Merapi 01',
            'plate_number' => 'AB 1234 XX',
            'brand' => 'Toyota',
            'model' => 'Land Cruiser',
            'year' => 2020,
            'capacity' => 4,
            'status' => 'available',
            'notes' => 'Unit utama.',
        ])->assertCreated()
            ->assertJsonPath('data.plate_number', 'AB 1234 XX');

        $id = $response->json('data.id');

        $this->putJson("/api/v1/admin/vehicles/{$id}", [
            'status' => 'maintenance',
            'notes' => 'Servis berkala.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'maintenance');

        $this->deleteJson("/api/v1/admin/vehicles/{$id}")
            ->assertOk();

        $this->assertDatabaseMissing('vehicles', ['id' => $id]);
    }

    public function test_admin_can_filter_and_search_vehicles(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        Vehicle::factory()->create([
            'name' => 'Jeep Merapi',
            'plate_number' => 'AB 1111 AA',
            'status' => 'available',
        ]);
        Vehicle::factory()->create([
            'name' => 'Jeep Kaliurang',
            'plate_number' => 'AB 2222 BB',
            'status' => 'maintenance',
        ]);

        $this->getJson('/api/v1/admin/vehicles?status=available')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.plate_number', 'AB 1111 AA');

        $this->getJson('/api/v1/admin/vehicles?search=2222')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Jeep Kaliurang');
    }

    public function test_plate_number_must_be_unique(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        Vehicle::factory()->create(['plate_number' => 'AB 9999 ZZ']);

        $this->postJson('/api/v1/admin/vehicles', [
            'name' => 'Duplikat',
            'plate_number' => 'AB 9999 ZZ',
            'capacity' => 4,
            'status' => 'available',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('plate_number');
    }
}
