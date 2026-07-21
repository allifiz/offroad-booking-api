<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSharedLayoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_customer_pages_render_shared_navigation(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($admin)
            ->get('/admin/customers')
            ->assertOk()
            ->assertSee('Admin Panel')
            ->assertSee('Travel Groups')
            ->assertSee('Customers')
            ->assertSee('Audit Logs')
            ->assertSee('Menu');

        $this->actingAs($admin)
            ->get("/admin/customers/{$customer->id}")
            ->assertOk()
            ->assertSee($customer->name)
            ->assertSee('Simpan status');
    }

    public function test_vehicle_pages_render_shared_navigation(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/vehicles')
            ->assertOk()
            ->assertSee('Admin Panel')
            ->assertSee('Kendaraan')
            ->assertSee('Tambah kendaraan')
            ->assertSee('Menu');

        $this->actingAs($admin)
            ->get('/admin/vehicles/create')
            ->assertOk()
            ->assertSee('Tambah kendaraan')
            ->assertSee('Simpan kendaraan');
    }

    public function test_travel_group_pages_render_shared_navigation(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/travel-groups')
            ->assertOk()
            ->assertSee('Admin Panel')
            ->assertSee('Travel Groups')
            ->assertSee('Buat group')
            ->assertSee('Menu');

        $this->actingAs($admin)
            ->get('/admin/travel-groups/create')
            ->assertOk()
            ->assertSee('Buat travel group')
            ->assertSee('Simpan travel group');
    }

    public function test_booking_and_payment_indexes_render_shared_navigation(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/bookings')
            ->assertOk()
            ->assertSee('Admin Panel')
            ->assertSee('Bookings')
            ->assertSee('Belum ada booking.')
            ->assertSee('Menu');

        $this->actingAs($admin)
            ->get('/admin/payments')
            ->assertOk()
            ->assertSee('Admin Panel')
            ->assertSee('Verifikasi pembayaran')
            ->assertSee('Tidak ada pembayaran.')
            ->assertSee('Menu');
    }

    public function test_non_admin_still_cannot_render_shared_admin_pages(): void
    {
        $customer = User::factory()->customer()->create();

        foreach (['/admin/customers', '/admin/vehicles', '/admin/travel-groups', '/admin/bookings', '/admin/payments'] as $path) {
            $this->actingAs($customer)
                ->get($path)
                ->assertForbidden();
        }
    }
}
