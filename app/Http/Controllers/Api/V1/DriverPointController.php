<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PointLedgerType;
use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\PointLedger;
use App\Models\Withdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DriverPointController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $profile = $request->user()->driverProfile;
        abort_unless($profile, 404);

        return response()->json([
            'success' => true,
            'data' => [
                'available_points' => $profile->available_points,
                'held_points' => $profile->held_points,
                'rupiah_per_point' => config('offroad.rupiah_per_point'),
                'available_amount' => $profile->available_points * config('offroad.rupiah_per_point'),
                'held_amount' => $profile->held_points * config('offroad.rupiah_per_point'),
            ],
        ]);
    }

    public function ledger(Request $request): JsonResponse
    {
        $profile = $request->user()->driverProfile;
        abort_unless($profile, 404);

        $validated = $request->validate([
            'type' => ['nullable', 'string', 'in:'.implode(',', array_column(PointLedgerType::cases(), 'value'))],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $items = PointLedger::query()
            ->where('driver_profile_id', $profile->id)
            ->when($validated['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->latest('occurred_at')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function withdrawals(Request $request): JsonResponse
    {
        $profile = $request->user()->driverProfile;
        abort_unless($profile, 404);

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:'.implode(',', array_column(WithdrawalStatus::cases(), 'value'))],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $items = Withdrawal::query()
            ->where('driver_profile_id', $profile->id)
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('requested_at')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function requestWithdrawal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'points' => ['required', 'integer', 'min:'.config('offroad.minimum_withdrawal_points')],
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_name' => ['required', 'string', 'max:100'],
        ]);

        $withdrawal = DB::transaction(function () use ($request, $validated): Withdrawal {
            $profile = $request->user()->driverProfile()->lockForUpdate()->firstOrFail();

            if ($profile->available_points < $validated['points']) {
                throw ValidationException::withMessages([
                    'points' => ['Saldo poin tersedia tidak mencukupi.'],
                ]);
            }

            $profile->decrement('available_points', $validated['points']);
            $profile->increment('held_points', $validated['points']);
            $profile->refresh();

            $withdrawal = Withdrawal::query()->create([
                'driver_profile_id' => $profile->id,
                'points' => $validated['points'],
                'amount' => $validated['points'] * config('offroad.rupiah_per_point'),
                'status' => WithdrawalStatus::PENDING,
                'bank_name' => $validated['bank_name'],
                'account_number' => $validated['account_number'],
                'account_name' => $validated['account_name'],
                'requested_at' => now(),
            ]);

            PointLedger::query()->create([
                'driver_profile_id' => $profile->id,
                'type' => PointLedgerType::HOLD,
                'points' => $validated['points'],
                'available_balance_after' => $profile->available_points,
                'held_balance_after' => $profile->held_points,
                'reference_type' => Withdrawal::class,
                'reference_id' => $withdrawal->id,
                'description' => 'Poin ditahan untuk pengajuan withdrawal.',
                'occurred_at' => now(),
            ]);

            return $withdrawal;
        });

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan withdrawal berhasil dibuat.',
            'data' => $withdrawal,
        ], 201);
    }
}
