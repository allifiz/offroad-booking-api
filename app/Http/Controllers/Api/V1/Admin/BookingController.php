<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\BookingStatus;
use App\Enums\DriverAssignmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\PointLedgerType;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PointLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

        return response()->json(['success' => true, 'data' => $bookings]);
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

        $currentStatus = $booking->status;
        $nextStatus = BookingStatus::from($validated['status']);
        $allowedTransitions = [
            BookingStatus::PENDING->value => [BookingStatus::CONFIRMED, BookingStatus::CANCELLED],
            BookingStatus::CONFIRMED->value => [BookingStatus::ONGOING, BookingStatus::CANCELLED],
            BookingStatus::ONGOING->value => [BookingStatus::COMPLETED],
            BookingStatus::COMPLETED->value => [],
            BookingStatus::CANCELLED->value => [],
        ];

        if (! in_array($nextStatus, $allowedTransitions[$currentStatus->value], true)) {
            throw ValidationException::withMessages([
                'status' => ["Transisi status dari {$currentStatus->value} ke {$nextStatus->value} tidak diizinkan."],
            ]);
        }

        if ($nextStatus === BookingStatus::CONFIRMED && $booking->payment_status !== PaymentStatus::PAID) {
            throw ValidationException::withMessages([
                'payment_status' => ['Booking harus berstatus paid sebelum dapat dikonfirmasi.'],
            ]);
        }

        if (in_array($nextStatus, [BookingStatus::ONGOING, BookingStatus::COMPLETED], true)) {
            if ($booking->payment_status !== PaymentStatus::PAID) {
                throw ValidationException::withMessages([
                    'payment_status' => ['Booking harus berstatus paid sebelum dapat dimulai atau diselesaikan.'],
                ]);
            }

            if (! $booking->driverAssignments()->where('status', DriverAssignmentStatus::ACCEPTED->value)->exists()) {
                throw ValidationException::withMessages([
                    'status' => ['Driver harus menerima assignment sebelum booking dapat dimulai atau diselesaikan.'],
                ]);
            }
        }

        DB::transaction(function () use ($booking, $nextStatus): void {
            $lockedBooking = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            $lockedBooking->update(['status' => $nextStatus]);

            if ($nextStatus === BookingStatus::CANCELLED) {
                $lockedBooking->driverAssignments()
                    ->whereIn('status', [DriverAssignmentStatus::OFFERED->value, DriverAssignmentStatus::ACCEPTED->value])
                    ->update([
                        'status' => DriverAssignmentStatus::CANCELLED->value,
                        'responded_at' => now(),
                    ]);
            }

            if ($nextStatus === BookingStatus::COMPLETED) {
                $points = config('offroad.points_per_completed_trip');
                $assignments = $lockedBooking->driverAssignments()
                    ->where('status', DriverAssignmentStatus::ACCEPTED->value)
                    ->with('driver.driverProfile')
                    ->get();

                foreach ($assignments as $assignment) {
                    $profile = $assignment->driver?->driverProfile;
                    if (! $profile) {
                        continue;
                    }

                    $alreadyAwarded = PointLedger::query()
                        ->where('driver_profile_id', $profile->id)
                        ->where('type', PointLedgerType::CREDIT->value)
                        ->where('reference_type', Booking::class)
                        ->where('reference_id', $lockedBooking->id)
                        ->exists();

                    if ($alreadyAwarded) {
                        continue;
                    }

                    $lockedProfile = $profile->newQuery()->lockForUpdate()->findOrFail($profile->id);
                    $lockedProfile->increment('available_points', $points);
                    $lockedProfile->refresh();

                    PointLedger::query()->create([
                        'driver_profile_id' => $lockedProfile->id,
                        'type' => PointLedgerType::CREDIT,
                        'points' => $points,
                        'available_balance_after' => $lockedProfile->available_points,
                        'held_balance_after' => $lockedProfile->held_points,
                        'reference_type' => Booking::class,
                        'reference_id' => $lockedBooking->id,
                        'description' => "Reward trip selesai {$lockedBooking->booking_code}.",
                        'occurred_at' => now(),
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Status booking berhasil diperbarui.',
            'data' => $booking->refresh()->load(['customer', 'tourPackage', 'participants', 'driverAssignments']),
        ]);
    }
}
