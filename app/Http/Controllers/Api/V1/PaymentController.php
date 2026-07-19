<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::query()
            ->with('booking.tourPackage')
            ->where('customer_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json(['success' => true, 'data' => $payments]);
    }

    public function store(Request $request, Booking $booking): JsonResponse
    {
        abort_unless($booking->customer_id === $request->user()->id, Response::HTTP_NOT_FOUND);

        $validated = $request->validate([
            'method' => ['required', 'string', 'in:bank_transfer'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        if (in_array($booking->status, [BookingStatus::COMPLETED, BookingStatus::CANCELLED], true)) {
            throw ValidationException::withMessages(['booking' => ['Booking final tidak dapat dibayar.']]);
        }

        if ($booking->payment_status === PaymentStatus::PAID) {
            throw ValidationException::withMessages(['booking' => ['Booking sudah dibayar.']]);
        }

        if ($booking->payments()->where('status', PaymentStatus::PENDING->value)->exists()) {
            throw ValidationException::withMessages(['booking' => ['Masih ada pembayaran yang menunggu verifikasi.']]);
        }

        $payment = DB::transaction(function () use ($request, $booking, $validated): Payment {
            $path = $request->file('proof')->store('payment-proofs', 'public');

            $payment = $booking->payments()->create([
                'customer_id' => $request->user()->id,
                'amount' => $booking->total_amount,
                'method' => $validated['method'],
                'proof_path' => $path,
                'status' => PaymentStatus::PENDING,
                'submitted_at' => now(),
            ]);

            $booking->update(['payment_status' => PaymentStatus::PENDING]);

            return $payment->load('booking');
        });

        return response()->json([
            'success' => true,
            'message' => 'Bukti pembayaran berhasil dikirim dan menunggu verifikasi admin.',
            'data' => $payment,
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, Payment $payment): JsonResponse
    {
        abort_unless($payment->customer_id === $request->user()->id, Response::HTTP_NOT_FOUND);

        return response()->json([
            'success' => true,
            'data' => $payment->load('booking.tourPackage'),
        ]);
    }
}
