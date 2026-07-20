<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWebFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Masuk sebagai admin');
    }

    public function test_active_admin_can_login_view_dashboard_and_logout(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
            'password' => 'password',
        ]);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Operations overview')
            ->assertSee($admin->name);

        $this->post('/admin/logout')
            ->assertRedirect('/admin/login');

        $this->assertGuest();
    }

    public function test_non_admin_or_inactive_admin_cannot_enter_admin_panel(): void
    {
        $customer = User::factory()->create([
            'role' => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->actingAs($customer)
            ->get('/admin')
            ->assertForbidden();

        auth()->logout();

        $inactiveAdmin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'status' => UserStatus::INACTIVE,
            'password' => 'password',
        ]);

        $this->post('/admin/login', [
            'email' => $inactiveAdmin->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
