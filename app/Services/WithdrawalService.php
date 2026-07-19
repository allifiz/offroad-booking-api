<?php

namespace App\Services;

use App\Enums\PointLedgerType;
use App\Enums\WithdrawalStatus;
use App\Models\DriverProfile;
use App\Models\PointLedger;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WithdrawalService
{
    public function request(DriverProfile $driverProfile, array $data): Withdrawal
    {
        return DB::transaction(function () use ($driverProfile, $data): Withdrawal {
            $profile = DriverProfile::query()
                ->whereKey($driverProfile->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($profile->available_points < $data['points']) {
                throw ValidationException::withMessages([
                    'points' => ['Saldo poin tersedia tidak mencukupi.'],
                ]);
            }

            $profile->decrement('available_points', $data['points']);
            $profile->increment('held_points', $data['points']);
            $profile->refresh();

            $withdrawal = Withdrawal::query()->create([
                'driver_profile_id' => $profile->id,
                'points' => $data['points'],
                'amount' => $data['points'] * config('offroad.rupiah_per_point'),
                'status' => WithdrawalStatus::PENDING,
                'bank_name' => $data['bank_name'],
                'account_number' => $data['account_number'],
                'account_name' => $data['account_name'],
                'requested_at' => now(),
            ]);

            PointLedger::query()->create([
                'driver_profile_id' => $profile->id,
                'type' => PointLedgerType::HOLD,
                'points' => $data['points'],
                'available_balance_after' => $profile->available_points,
                'held_balance_after' => $profile->held_points,
                'reference_type' => Withdrawal::class,
                'reference_id' => $withdrawal->id,
                'description' => 'Poin ditahan untuk pengajuan withdrawal.',
                'occurred_at' => now(),
            ]);

            return $withdrawal;
        }, 3);
    }
}
