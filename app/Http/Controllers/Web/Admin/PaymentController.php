<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Notifications\OperationalNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $payments = Payment::query()
            ->with(['booking.tourPackage', 'customer'])
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->whereHas('booking', fn ($query) => $query->where('booking_code', 'like', "%{$search}%"))
                        ->orWhereHas('customer', function ($query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = Payment::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('admin.payments.index', compact('payments', 'counts'));
    }

    public function show(Payment $payment): View
    {
        $payment->load(['booking.tourPackage', 'customer', 'reviewer']);

        return view('admin.payments.show', compact('payment'));
    }

    public function update(Request $request, Payment $payment): RedirectResponse
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
                'payment_status' => $newStatus,
            ]);

            $payment->customer->notify(new OperationalNotification(
                event: $newStatus === PaymentStatus::PAID ? 'payment.approved' : 'payment.rejected',
                title: $newStatus === PaymentStatus::PAID ? 'Pembayaran disetujui' : 'Pembayaran ditolak',
                message: $newStatus === PaymentStatus::PAID
                    ? "Pembayaran untuk booking {$payment->booking->booking_code} telah disetujui."
                    : "Pembayaran untuk booking {$payment->booking->booking_code} ditolak. Silakan unggah ulang bukti pembayaran.",
                resourceType: 'payment',
                resourceId: $payment->id,
                meta: ['booking_id' => $payment->booking_id],
            ));
        });

        return redirect()
            ->route('admin.payments.show', $payment)
            ->with('success', $validated['status'] === PaymentStatus::PAID->value
                ? 'Pembayaran berhasil disetujui.'
                : 'Pembayaran berhasil ditolak.');
    }
}
