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

        $this->actingAs($admin)->get('/admin/customers')->assertOk()->assertSee('Admin Panel')->assertSee('Travel Groups')->assertSee('Customers')->assertSee('Audit Logs')->assertSee('Menu');
        $this->actingAs($admin)->get("/admin/customers/{$customer->id}")->assertOk()->assertSee($customer->name)->assertSee('Simpan status');
    }

    public function test_master_and_operational_indexes_render_shared_navigation(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ([
            ['/admin/vehicles', 'Kendaraan'],
            ['/admin/travel-groups', 'Travel Groups'],
            ['/admin/bookings', 'Bookings'],
            ['/admin/payments', 'Verifikasi pembayaran'],
            ['/admin/drivers', 'Driver Verification'],
            ['/admin/withdrawals', 'Withdrawal Queue'],
        ] as [$path, $text]) {
            $this->actingAs($admin)->get($path)->assertOk()->assertSee('Admin Panel')->assertSee($text)->assertSee('Menu');
        }
    }

    public function test_reports_audit_logs_and_dashboard_render_shared_navigation(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/reports')->assertOk()->assertSee('Admin Panel')->assertSee('Export laporan CSV')->assertSee('Download CSV')->assertSee('Menu');
        $this->actingAs($admin)->get('/admin/audit-logs')->assertOk()->assertSee('Admin Panel')->assertSee('Audit Logs')->assertSee('Belum ada audit log.')->assertSee('Menu');
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Admin Panel')->assertSee('Dashboard')->assertSee('Booking terbaru')->assertSee('Reporting center')->assertSee('Menu');
    }

    public function test_create_pages_keep_shared_navigation(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/vehicles/create')->assertOk()->assertSee('Admin Panel')->assertSee('Simpan kendaraan');
        $this->actingAs($admin)->get('/admin/travel-groups/create')->assertOk()->assertSee('Admin Panel')->assertSee('Simpan travel group');
        $this->actingAs($admin)->get('/admin/tour-packages/create')->assertOk()->assertSee('Admin Panel')->assertSee('Simpan paket');
    }

    public function test_non_admin_still_cannot_render_shared_admin_pages(): void
    {
        $customer = User::factory()->customer()->create();

        foreach (['/admin', '/admin/customers', '/admin/tour-packages', '/admin/vehicles', '/admin/travel-groups', '/admin/bookings', '/admin/payments', '/admin/drivers', '/admin/withdrawals', '/admin/reports', '/admin/audit-logs'] as $path) {
            $this->actingAs($customer)->get($path)->assertForbidden();
        }
    }
}