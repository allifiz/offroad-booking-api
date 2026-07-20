<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDashboardFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_empty_dashboard_metrics(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::ADMIN]));

        $this->getJson('/api/v1/admin/dashboard?date_from=2026-07-01&date_to=2026-07-30')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.period.date_from', '2026-07-01')
            ->assertJsonPath('data.period.date_to', '2026-07-30')
            ->assertJsonPath('data.period.days', 30)
            ->assertJsonPath('data.bookings.total', 0)
            ->assertJsonPath('data.payments.paid_revenue', 0)
            ->assertJsonPath('data.drivers.total', 0)
            ->assertJsonPath('data.vehicles.total', 0)
            ->assertJsonPath('data.withdrawals.total', 0)
            ->assertJsonCount(30, 'data.trend');
    }

    public function test_non_admin_cannot_view_dashboard_metrics(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::CUSTOMER]));

        $this->getJson('/api/v1/admin/dashboard')->assertForbidden();
    }

    public function test_dashboard_rejects_invalid_or_excessive_period(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => UserRole::ADMIN]));

        $this->getJson('/api/v1/admin/dashboard?date_from=2026-07-30&date_to=2026-07-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_to');

        $this->getJson('/api/v1/admin/dashboard?date_from=2025-01-01&date_to=2026-07-30')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_from');
    }
}
