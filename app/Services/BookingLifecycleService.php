<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\DriverAssignmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\PointLedgerType;
use App\Models\Booking;
use App\Models\PointLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingLifecycleService
{
    public function transition(Booking $booking, BookingStatus $nextStatus): Booking
    {
        return DB::transaction(function () use ($booking, $nextStatus): Booking {
            $lockedBooking = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            $currentStatus = $lockedBooking->status;

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

            if ($nextStatus === BookingStatus::CONFIRMED && $lockedBooking->payment_status !== PaymentStatus::PAID) {
                throw ValidationException::withMessages([
                    'payment_status' => ['Booking harus berstatus paid sebelum dapat dikonfirmasi.'],
                ]);
            }

            if (in_array($nextStatus, [BookingStatus::ONGOING, BookingStatus::COMPLETED], true)) {
                if ($lockedBooking->payment_status !== PaymentStatus::PAID) {
                    throw ValidationException::withMessages([
                        'payment_status' => ['Booking harus berstatus paid sebelum dapat dimulai atau diselesaikan.'],
                    ]);
                }

                if (! $lockedBooking->driverAssignments()->where('status', DriverAssignmentStatus::ACCEPTED->value)->exists()) {
                    throw ValidationException::withMessages([
                        'status' => ['Driver harus menerima assignment sebelum booking dapat dimulai atau diselesaikan.'],
                    ]);
                }
            }

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
                $this->awardCompletionPoints($lockedBooking);
            }

            return $lockedBooking->refresh();
        }, 3);
    }

    private function awardCompletionPoints(Booking $booking): void
    {
        $points = (int) config('offroad.points_per_completed_trip');
        $assignments = $booking->driverAssignments()
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
                ->where('reference_id', $booking->id)
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
                'reference_id' => $booking->id,
                'description' => "Reward trip selesai {$booking->booking_code}.",
                'occurred_at' => now(),
            ]);
        }
    }
}
