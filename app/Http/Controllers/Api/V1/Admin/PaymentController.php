<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $payments = Payment::query()
            ->with(['booking.tourPackage', 'customer'])
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate($validated['per_page'] ?? 10)
            ->withQueryString();

        return response()->json(['success' => true, 'data' => $payments]);
    }

    public function show(Payment $payment): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $payment->load(['booking.tourPackage', 'customer', 'reviewer']),
        ]);
    }

    public function update(Request $request, Payment $payment): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([PaymentStatus::PAID->value, PaymentStatus::FAILED->value])],
            'rejection_reason' => [
                Rule::requiredIf($request->input('status') === PaymentStatus::FAILED->value),
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        if ($payment->status !== PaymentStatus::PENDING) {
            throw ValidationException::withMessages([
                'status' => ['Hanya pembayaran pending yang dapat diverifikasi.'],
            ]);
        }

        DB::transaction(function () use ($request, $payment, $validated): void {
            $newStatus = PaymentStatus::from($validated['status']);

            $payment->update([
                'status' => $newStatus,
                'rejection_reason' => $newStatus === PaymentStatus::FAILED ? $validated['rejection_reason'] : null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            $payment->booking()->update([
                'payment_status' => $newStatus === PaymentStatus::PAID
                    ? PaymentStatus::PAID
                    : PaymentStatus::FAILED,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => $payment->fresh()->status === PaymentStatus::PAID
                ? 'Pembayaran berhasil disetujui.'
                : 'Pembayaran ditolak.',
            'data' => $payment->fresh()->load(['booking', 'customer', 'reviewer']),
        ]);
    }
}
