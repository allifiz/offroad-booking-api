<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\BookingStatus;
use App\Enums\DriverAssignmentStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:'.implode(',', array_column(BookingStatus::cases(), 'value'))],
            'payment_status' => ['nullable', 'string', 'in:'.implode(',', array_column(PaymentStatus::cases(), 'value'))],
            'tour_date' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $bookings = Booking::query()
            ->with(['customer', 'tourPackage', 'participants', 'driverAssignments.driver', 'driverAssignments.vehicle'])
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validated['payment_status'] ?? null, fn ($query, $status) => $query->where('payment_status', $status))
            ->when($validated['tour_date'] ?? null, fn ($query, $date) => $query->whereDate('tour_date', $date))
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('booking_code', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($validated['per_page'] ?? 10)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    public function show(Booking $booking): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $booking->load([
                'customer',
                'tourPackage',
                'participants',
                'driverAssignments.driver.driverProfile',
                'driverAssignments.vehicle',
                'driverAssignments.offeredBy',
            ]),
        ]);
    }

    public function updateStatus(Request $request, Booking $booking): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', array_column(BookingStatus::cases(), 'value'))],
        ]);

        $nextStatus = BookingStatus::from($validated['status']);

        if ($booking->status === BookingStatus::COMPLETED || $booking->status === BookingStatus::CANCELLED) {
            return response()->json([
                'success' => false,
                'message' => 'Status booking final tidak dapat diubah.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (in_array($nextStatus, [BookingStatus::ONGOING, BookingStatus::COMPLETED], true)) {
            if ($booking->payment_status !== PaymentStatus::PAID) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembayaran booking belum selesai.',
                    'errors' => [
                        'payment_status' => ['Booking harus berstatus paid sebelum dapat dimulai atau diselesaikan.'],
                    ],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $hasAcceptedAssignment = $booking->driverAssignments()
                ->where('status', DriverAssignmentStatus::ACCEPTED->value)
                ->exists();

            if (! $hasAcceptedAssignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking belum memiliki assignment driver yang diterima.',
                    'errors' => [
                        'status' => ['Driver harus menerima assignment sebelum booking dapat dimulai atau diselesaikan.'],
                    ],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $booking->update(['status' => $nextStatus]);

        if ($nextStatus === BookingStatus::CANCELLED) {
            $booking->driverAssignments()
                ->whereIn('status', [DriverAssignmentStatus::OFFERED->value, DriverAssignmentStatus::ACCEPTED->value])
                ->update([
                    'status' => DriverAssignmentStatus::CANCELLED->value,
                    'responded_at' => now(),
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status booking berhasil diperbarui.',
            'data' => $booking->refresh()->load(['customer', 'tourPackage', 'participants', 'driverAssignments']),
        ]);
    }
}
