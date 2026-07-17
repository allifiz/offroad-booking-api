<?php

namespace Tests\Feature\Api\V1;

use App\Enums\TourPackageStatus;
use App\Models\TourPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TourPackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_only_sees_active_packages(): void
    {
        TourPackage::factory()->create(['status' => TourPackageStatus::ACTIVE]);
        TourPackage::factory()->create(['status' => TourPackageStatus::DRAFT]);

        $this->getJson('/api/v1/tour-packages')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_non_admin_cannot_manage_packages(): void
    {
        Sanctum::actingAs(User::factory()->customer()->create());

        $this->postJson('/api/v1/admin/tour-packages', [])->assertForbidden();
    }

    public function test_admin_can_create_update_and_delete_package(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $response = $this->postJson('/api/v1/admin/tour-packages', [
            'name' => 'Sunrise Merapi',
            'description' => 'Paket offroad pagi.',
            'meeting_point' => 'Basecamp Kaliurang',
            'duration_minutes' => 180,
            'minimum_participants' => 2,
            'maximum_participants' => 20,
            'price_per_person' => 250000,
            'status' => 'active',
        ])->assertCreated();

        $id = $response->json('data.id');

        $this->putJson("/api/v1/admin/tour-packages/{$id}", [
            'price_per_person' => 275000,
        ])->assertOk()->assertJsonPath('data.price_per_person', '275000.00');

        $this->deleteJson("/api/v1/admin/tour-packages/{$id}")
            ->assertOk();

        $this->assertDatabaseMissing('tour_packages', ['id' => $id]);
    }
}
