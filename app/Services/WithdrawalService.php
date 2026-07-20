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
            $profile = DriverProfile::query()->whereKey($driverProfile->id)->lockForUpdate()->firstOrFail();

            if ($profile->available_points < $data['points']) {
                throw ValidationException::withMessages(['points' => ['Saldo poin tersedia tidak mencukupi.']]);
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

            $this->recordLedger($profile, $withdrawal, PointLedgerType::HOLD, 'Poin ditahan untuk pengajuan withdrawal.');

            return $withdrawal;
        }, 3);
    }

    public function transition(Withdrawal $withdrawal, WithdrawalStatus $nextStatus, int $processorId, ?string $rejectionReason = null): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $nextStatus, $processorId, $rejectionReason): Withdrawal {
            $locked = Withdrawal::query()->lockForUpdate()->findOrFail($withdrawal->id);
            $profile = $locked->driverProfile()->lockForUpdate()->firstOrFail();

            $allowed = [
                WithdrawalStatus::PENDING->value => [WithdrawalStatus::APPROVED, WithdrawalStatus::REJECTED],
                WithdrawalStatus::APPROVED->value => [WithdrawalStatus::PAID],
                WithdrawalStatus::REJECTED->value => [],
                WithdrawalStatus::PAID->value => [],
                WithdrawalStatus::CANCELLED->value => [],
            ];

            if (! in_array($nextStatus, $allowed[$locked->status->value], true)) {
                throw ValidationException::withMessages([
                    'status' => ["Transisi withdrawal dari {$locked->status->value} ke {$nextStatus->value} tidak diizinkan."],
                ]);
            }

            if ($nextStatus === WithdrawalStatus::REJECTED) {
                if ($profile->held_points < $locked->points) {
                    throw ValidationException::withMessages(['points' => ['Saldo held driver tidak konsisten.']]);
                }

                $profile->decrement('held_points', $locked->points);
                $profile->increment('available_points', $locked->points);
                $profile->refresh();
                $this->recordLedger($profile, $locked, PointLedgerType::RELEASE, 'Poin dikembalikan karena withdrawal ditolak.');
            }

            if ($nextStatus === WithdrawalStatus::PAID) {
                if ($profile->held_points < $locked->points) {
                    throw ValidationException::withMessages(['points' => ['Saldo held driver tidak mencukupi.']]);
                }

                $profile->decrement('held_points', $locked->points);
                $profile->refresh();
                $this->recordLedger($profile, $locked, PointLedgerType::DEBIT, 'Withdrawal telah dibayar.');
            }

            $locked->update([
                'status' => $nextStatus,
                'rejection_reason' => $nextStatus === WithdrawalStatus::REJECTED ? $rejectionReason : null,
                'processed_by' => $processorId,
                'processed_at' => now(),
            ]);

            return $locked->refresh();
        }, 3);
    }

    private function recordLedger(DriverProfile $profile, Withdrawal $withdrawal, PointLedgerType $type, string $description): void
    {
        PointLedger::query()->create([
            'driver_profile_id' => $profile->id,
            'type' => $type,
            'points' => $withdrawal->points,
            'available_balance_after' => $profile->available_points,
            'held_balance_after' => $profile->held_points,
            'reference_type' => Withdrawal::class,
            'reference_id' => $withdrawal->id,
            'description' => $description,
            'occurred_at' => now(),
        ]);
    }
}
