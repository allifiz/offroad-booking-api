<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        return response()->json(['success' => true, 'data' => $withdrawal->load(['driverProfile.user', 'processor'])]);
    }

    public function update(Request $request, Withdrawal $withdrawal, WithdrawalService $service): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                WithdrawalStatus::APPROVED->value,
                WithdrawalStatus::REJECTED->value,
                WithdrawalStatus::PAID->value,
            ])],
            'rejection_reason' => [
                Rule::requiredIf($request->input('status') === WithdrawalStatus::REJECTED->value),
                'nullable', 'string', 'max:1000',
            ],
        ]);

        $updated = $service->transition(
            $withdrawal,
            WithdrawalStatus::from($validated['status']),
            $request->user()->id,
            $validated['rejection_reason'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Status withdrawal berhasil diperbarui.',
            'data' => $updated->load(['driverProfile.user', 'processor']),
        ]);
    }
}
