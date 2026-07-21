<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AdminWebCustomerFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_view_and_suspend_customer(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create(['status' => UserStatus::ACTIVE]);
        $customer->createToken('mobile');

        $this->actingAs($admin)
            ->get('/admin/customers')
            ->assertOk()
            ->assertSee($customer->email);

        $this->actingAs($admin)
            ->get("/admin/customers/{$customer->id}")
            ->assertOk()
            ->assertSee($customer->name);

        $this->actingAs($admin)
            ->patch("/admin/customers/{$customer->id}/status", [
                'status' => UserStatus::SUSPENDED->value,
            ])
            ->assertRedirect(route('admin.customers.show', $customer));

        $this->assertSame(UserStatus::SUSPENDED, $customer->fresh()->status);
        $this->assertSame(0, PersonalAccessToken::query()->where('tokenable_id', $customer->id)->count());
    }

    public function test_non_admin_cannot_manage_customers(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get('/admin/customers')->assertForbidden();
    }

    public function test_customer_routes_reject_non_customer_users(): void
    {
        $admin = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create();

        $this->actingAs($admin)->get("/admin/customers/{$driver->id}")->assertNotFound();
    }
}
