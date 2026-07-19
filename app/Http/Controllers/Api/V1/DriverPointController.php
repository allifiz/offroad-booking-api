<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PointLedgerType;
use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\PointLedger;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function requestWithdrawal(Request $request, WithdrawalService $withdrawalService): JsonResponse
    {
        $profile = $request->user()->driverProfile;
        abort_unless($profile, 404);

        $validated = $request->validate([
            'points' => ['required', 'integer', 'min:'.config('offroad.minimum_withdrawal_points')],
            'bank_name' => ['required', 'string', 'max:100'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_name' => ['required', 'string', 'max:100'],
        ]);

        $withdrawal = $withdrawalService->request($profile, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan withdrawal berhasil dibuat.',
            'data' => $withdrawal,
        ], 201);
    }
}
