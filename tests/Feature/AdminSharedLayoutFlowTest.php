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

    public function test_non_admin_still_cannot_render_shared_admin_pages(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get('/admin/customers')
            ->assertForbidden();
    }
}
