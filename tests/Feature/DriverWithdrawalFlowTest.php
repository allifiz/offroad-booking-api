<?php

namespace Tests\Feature;

use App\Enums\DriverStatus;
use App\Enums\PointLedgerType;
use App\Enums\VerificationStatus;
use App\Enums\WithdrawalStatus;
use App\Models\DriverProfile;
use App\Models\PointLedger;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DriverWithdrawalFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('offroad.minimum_withdrawal_points', 100);
        config()->set('offroad.rupiah_per_point', 1000);
    }

    public function test_driver_can_request_withdrawal_and_points_are_moved_to_held_balance(): void
    {
        [$driver, $profile] = $this->createDriverWithPoints(250);
        Sanctum::actingAs($driver);

        $response = $this->postJson('/api/v1/driver/withdrawals', [
            'points' => 100,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'Driver Test',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', WithdrawalStatus::PENDING->value)
            ->assertJsonPath('data.points', 100)
            ->assertJsonPath('data.amount', '100000.00');

        $profile->refresh();
        $this->assertSame(150, $profile->available_points);
        $this->assertSame(100, $profile->held_points);

        $withdrawal = Withdrawal::query()->firstOrFail();
        $this->assertDatabaseHas('point_ledgers', [
            'driver_profile_id' => $profile->id,
            'type' => PointLedgerType::HOLD->value,
            'points' => 100,
            'available_balance_after' => 150,
            'held_balance_after' => 100,
            'reference_type' => Withdrawal::class,
            'reference_id' => $withdrawal->id,
        ]);
    }

    public function test_driver_cannot_request_more_points_than_available_balance(): void
    {
        [$driver, $profile] = $this->createDriverWithPoints(100);
        Sanctum::actingAs($driver);

        $this->postJson('/api/v1/driver/withdrawals', [
            'points' => 200,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'Driver Test',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('points');

        $profile->refresh();
        $this->assertSame(100, $profile->available_points);
        $this->assertSame(0, $profile->held_points);
        $this->assertDatabaseCount('withdrawals', 0);
        $this->assertDatabaseCount('point_ledgers', 0);
    }

    public function test_rejected_withdrawal_releases_held_points_back_to_available_balance(): void
    {
        [$driver, $profile] = $this->createDriverWithPoints(200);
        Sanctum::actingAs($driver);

        $withdrawalId = $this->postJson('/api/v1/driver/withdrawals', [
            'points' => 100,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'Driver Test',
        ])->assertCreated()->json('data.id');

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/admin/withdrawals/{$withdrawalId}", [
            'status' => WithdrawalStatus::REJECTED->value,
            'rejection_reason' => 'Nama rekening tidak sesuai.',
        ])->assertOk()
            ->assertJsonPath('data.status', WithdrawalStatus::REJECTED->value);

        $profile->refresh();
        $this->assertSame(200, $profile->available_points);
        $this->assertSame(0, $profile->held_points);

        $this->assertDatabaseHas('point_ledgers', [
            'driver_profile_id' => $profile->id,
            'type' => PointLedgerType::RELEASE->value,
            'points' => 100,
            'available_balance_after' => 200,
            'held_balance_after' => 0,
        ]);
    }

    public function test_approved_withdrawal_can_be_paid_and_held_points_are_debited_once(): void
    {
        [$driver, $profile] = $this->createDriverWithPoints(200);
        Sanctum::actingAs($driver);

        $withdrawalId = $this->postJson('/api/v1/driver/withdrawals', [
            'points' => 100,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'Driver Test',
        ])->assertCreated()->json('data.id');

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/admin/withdrawals/{$withdrawalId}", [
            'status' => WithdrawalStatus::APPROVED->value,
        ])->assertOk();

        $profile->refresh();
        $this->assertSame(100, $profile->available_points);
        $this->assertSame(100, $profile->held_points);

        $this->patchJson("/api/v1/admin/withdrawals/{$withdrawalId}", [
            'status' => WithdrawalStatus::PAID->value,
        ])->assertOk()
            ->assertJsonPath('data.status', WithdrawalStatus::PAID->value);

        $profile->refresh();
        $this->assertSame(100, $profile->available_points);
        $this->assertSame(0, $profile->held_points);
        $this->assertSame(1, PointLedger::query()->where('type', PointLedgerType::DEBIT->value)->count());

        $this->patchJson("/api/v1/admin/withdrawals/{$withdrawalId}", [
            'status' => WithdrawalStatus::PAID->value,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $profile->refresh();
        $this->assertSame(0, $profile->held_points);
        $this->assertSame(1, PointLedger::query()->where('type', PointLedgerType::DEBIT->value)->count());
    }

    public function test_pending_withdrawal_cannot_skip_directly_to_paid(): void
    {
        [$driver, $profile] = $this->createDriverWithPoints(200);
        Sanctum::actingAs($driver);

        $withdrawalId = $this->postJson('/api/v1/driver/withdrawals', [
            'points' => 100,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'Driver Test',
        ])->assertCreated()->json('data.id');

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/admin/withdrawals/{$withdrawalId}", [
            'status' => WithdrawalStatus::PAID->value,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $profile->refresh();
        $this->assertSame(100, $profile->available_points);
        $this->assertSame(100, $profile->held_points);
        $this->assertDatabaseHas('withdrawals', [
            'id' => $withdrawalId,
            'status' => WithdrawalStatus::PENDING->value,
        ]);
        $this->assertSame(0, PointLedger::query()->where('type', PointLedgerType::DEBIT->value)->count());
    }

    /**
     * @return array{0: User, 1: DriverProfile}
     */
    private function createDriverWithPoints(int $availablePoints): array
    {
        $driver = User::factory()->driver()->create();
        $profile = DriverProfile::factory()->for($driver)->create([
            'status' => DriverStatus::AVAILABLE,
            'verification_status' => VerificationStatus::APPROVED,
            'available_points' => $availablePoints,
            'held_points' => 0,
        ]);

        return [$driver, $profile];
    }
}
