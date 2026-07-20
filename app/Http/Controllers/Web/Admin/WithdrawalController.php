<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(WithdrawalStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $withdrawals = Withdrawal::query()
            ->with(['driverProfile.user', 'processor'])
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->whereHas('driverProfile.user', fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->latest('requested_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.withdrawals.index', compact('withdrawals'));
    }

    public function show(Withdrawal $withdrawal): View
    {
        return view('admin.withdrawals.show', [
            'withdrawal' => $withdrawal->load(['driverProfile.user', 'processor']),
        ]);
    }

    public function update(Request $request, Withdrawal $withdrawal, WithdrawalService $service): RedirectResponse
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

        $service->transition(
            $withdrawal,
            WithdrawalStatus::from($validated['status']),
            $request->user()->id,
            $validated['rejection_reason'] ?? null,
        );

        return back()->with('success', 'Status withdrawal berhasil diperbarui.');
    }
}
