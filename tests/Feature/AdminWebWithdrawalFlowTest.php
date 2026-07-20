<?php

namespace Tests\Feature;

use App\Enums\DriverStatus;
use App\Enums\VerificationStatus;
use App\Enums\WithdrawalStatus;
use App\Models\DriverProfile;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWebWithdrawalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_and_pay_withdrawal(): void
    {
        $admin = User::factory()->admin()->create();
        $driver = User::factory()->driver()->create();
        $profile = DriverProfile::query()->create([
            'user_id' => $driver->id,
            'status' => DriverStatus::AVAILABLE,
            'verification_status' => VerificationStatus::APPROVED,
            'available_points' => 100,
            'held_points' => 200,
        ]);
        $withdrawal = Withdrawal::query()->create([
            'driver_profile_id' => $profile->id,
            'points' => 200,
            'amount' => 200000,
            'status' => WithdrawalStatus::PENDING,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'Driver Test',
            'requested_at' => now(),
        ]);

        $this->actingAs($admin)->get('/admin/withdrawals')->assertOk()->assertSee($driver->name);
        $this->actingAs($admin)->get("/admin/withdrawals/{$withdrawal->id}")->assertOk()->assertSee('BCA');

        $this->actingAs($admin)->patch("/admin/withdrawals/{$withdrawal->id}", ['status' => 'approved'])->assertRedirect();
        $this->assertSame(WithdrawalStatus::APPROVED, $withdrawal->fresh()->status);

        $this->actingAs($admin)->patch("/admin/withdrawals/{$withdrawal->id}", ['status' => 'paid'])->assertRedirect();
        $this->assertSame(WithdrawalStatus::PAID, $withdrawal->fresh()->status);
        $this->assertSame(0, $profile->fresh()->held_points);
        $this->assertDatabaseHas('point_ledgers', ['reference_id' => $withdrawal->id, 'type' => 'debit']);
    }

    public function test_rejection_requires_reason_and_non_admin_is_forbidden(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        $profile = DriverProfile::query()->create([
            'user_id' => User::factory()->driver()->create()->id,
            'status' => DriverStatus::AVAILABLE,
            'verification_status' => VerificationStatus::APPROVED,
            'available_points' => 0,
            'held_points' => 100,
        ]);
        $withdrawal = Withdrawal::query()->create([
            'driver_profile_id' => $profile->id,
            'points' => 100,
            'amount' => 100000,
            'status' => WithdrawalStatus::PENDING,
            'bank_name' => 'BRI',
            'account_number' => '111',
            'account_name' => 'Driver',
            'requested_at' => now(),
        ]);

        $this->actingAs($admin)->patch("/admin/withdrawals/{$withdrawal->id}", ['status' => 'rejected'])
            ->assertSessionHasErrors('rejection_reason');
        $this->actingAs($customer)->get('/admin/withdrawals')->assertForbidden();
    }
}
