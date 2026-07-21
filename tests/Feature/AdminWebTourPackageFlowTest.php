<?php

namespace Tests\Feature;

use App\Enums\TourPackageStatus;
use App\Models\TourPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWebTourPackageFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_tour_packages_from_web(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/tour-packages')
            ->assertOk()
            ->assertSee('Paket Wisata');

        $response = $this->actingAs($admin)->post('/admin/tour-packages', [
            'name' => 'Paket Sunrise',
            'slug' => '',
            'description' => 'Paket pengujian web admin.',
            'meeting_point' => 'Basecamp Utama',
            'duration_minutes' => 180,
            'minimum_participants' => 2,
            'maximum_participants' => 8,
            'price_per_person' => 250000,
            'status' => TourPackageStatus::ACTIVE->value,
        ]);

        $tourPackage = TourPackage::query()->where('name', 'Paket Sunrise')->firstOrFail();
        $response->assertRedirect(route('admin.tour-packages.edit', $tourPackage));
        $this->assertSame(TourPackageStatus::ACTIVE, $tourPackage->status);

        $this->actingAs($admin)
            ->put("/admin/tour-packages/{$tourPackage->id}", [
                'name' => 'Paket Sunrise Premium',
                'slug' => $tourPackage->slug,
                'description' => 'Paket diperbarui.',
                'meeting_point' => 'Basecamp Utama',
                'duration_minutes' => 240,
                'minimum_participants' => 2,
                'maximum_participants' => 10,
                'price_per_person' => 300000,
                'status' => TourPackageStatus::INACTIVE->value,
            ])
            ->assertRedirect(route('admin.tour-packages.edit', $tourPackage));

        $this->assertDatabaseHas('tour_packages', [
            'id' => $tourPackage->id,
            'name' => 'Paket Sunrise Premium',
            'status' => TourPackageStatus::INACTIVE->value,
        ]);

        $this->actingAs($admin)
            ->delete("/admin/tour-packages/{$tourPackage->id}")
            ->assertRedirect(route('admin.tour-packages.index'));

        $this->assertDatabaseMissing('tour_packages', ['id' => $tourPackage->id]);
    }

    public function test_non_admin_cannot_manage_tour_packages(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get('/admin/tour-packages')->assertForbidden();
    }
}
