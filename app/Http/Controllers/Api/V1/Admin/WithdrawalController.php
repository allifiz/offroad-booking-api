<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\PointLedgerType;
use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\PointLedger;
use App\Models\Withdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WithdrawalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(WithdrawalStatus::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $items = Withdrawal::query()
            ->with(['driverProfile.user', 'processor'])
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('requested_at')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function show(Withdrawal $withdrawal): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $withdrawal->load(['driverProfile.user', 'processor']),
        ]);
    }

    public function update(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                WithdrawalStatus::APPROVED->value,
                WithdrawalStatus::REJECTED->value,
                WithdrawalStatus::PAID->value,
            ])],
            'rejection_reason' => [
                Rule::requiredIf($request->input('status') === WithdrawalStatus::REJECTED->value),
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $nextStatus = WithdrawalStatus::from($validated['status']);
        $allowed = [
            WithdrawalStatus::PENDING->value => [WithdrawalStatus::APPROVED, WithdrawalStatus::REJECTED],
            WithdrawalStatus::APPROVED->value => [WithdrawalStatus::PAID],
            WithdrawalStatus::REJECTED->value => [],
            WithdrawalStatus::PAID->value => [],
            WithdrawalStatus::CANCELLED->value => [],
        ];

        if (! in_array($nextStatus, $allowed[$withdrawal->status->value], true)) {
            throw ValidationException::withMessages([
                'status' => ["Transisi withdrawal dari {$withdrawal->status->value} ke {$nextStatus->value} tidak diizinkan."],
            ]);
        }

        DB::transaction(function () use ($request, $withdrawal, $validated, $nextStatus): void {
            $locked = Withdrawal::query()->lockForUpdate()->findOrFail($withdrawal->id);
            $profile = $locked->driverProfile()->lockForUpdate()->firstOrFail();

            if ($nextStatus === WithdrawalStatus::REJECTED) {
                if ($profile->held_points < $locked->points) {
                    throw ValidationException::withMessages(['points' => ['Saldo held driver tidak konsisten.']]);
                }

                $profile->decrement('held_points', $locked->points);
                $profile->increment('available_points', $locked->points);
                $profile->refresh();

                PointLedger::query()->create([
                    'driver_profile_id' => $profile->id,
                    'type' => PointLedgerType::RELEASE,
                    'points' => $locked->points,
                    'available_balance_after' => $profile->available_points,
                    'held_balance_after' => $profile->held_points,
                    'reference_type' => Withdrawal::class,
                    'reference_id' => $locked->id,
                    'description' => 'Poin dikembalikan karena withdrawal ditolak.',
                    'occurred_at' => now(),
                ]);
            }

            if ($nextStatus === WithdrawalStatus::PAID) {
                if ($profile->held_points < $locked->points) {
                    throw ValidationException::withMessages(['points' => ['Saldo held driver tidak mencukupi.']]);
                }

                $profile->decrement('held_points', $locked->points);
                $profile->refresh();

                PointLedger::query()->create([
                    'driver_profile_id' => $profile->id,
                    'type' => PointLedgerType::DEBIT,
                    'points' => $locked->points,
                    'available_balance_after' => $profile->available_points,
                    'held_balance_after' => $profile->held_points,
                    'reference_type' => Withdrawal::class,
                    'reference_id' => $locked->id,
                    'description' => 'Withdrawal telah dibayar.',
                    'occurred_at' => now(),
                ]);
            }

            $locked->update([
                'status' => $nextStatus,
                'rejection_reason' => $nextStatus === WithdrawalStatus::REJECTED ? $validated['rejection_reason'] : null,
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Status withdrawal berhasil diperbarui.',
            'data' => $withdrawal->refresh()->load(['driverProfile.user', 'processor']),
        ]);
    }
}
