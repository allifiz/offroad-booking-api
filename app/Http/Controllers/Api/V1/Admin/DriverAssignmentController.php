<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\BookingStatus;
use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverStatus;
use App\Enums\PaymentStatus;
use App\Enums\VehicleStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\DriverAssignment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class DriverAssignmentController extends Controller
{
    public function store(Request $request, Booking $booking): JsonResponse
    {
        $validated = $request->validate([
            'driver_id' => ['required', 'integer', 'exists:users,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
        ]);

        if (in_array($booking->status, [BookingStatus::CANCELLED, BookingStatus::COMPLETED], true)) {
            throw ValidationException::withMessages([
                'booking' => ['Booking yang sudah selesai atau dibatalkan tidak dapat diberi assignment.'],
            ]);
        }

        if ($booking->payment_status !== PaymentStatus::PAID) {
            throw ValidationException::withMessages([
                'payment_status' => ['Pembayaran booking harus berstatus paid sebelum driver dapat di-assign.'],
            ]);
        }

        $driver = User::query()->with('driverProfile')->findOrFail($validated['driver_id']);
        $vehicle = Vehicle::query()->with('driverProfile')->findOrFail($validated['vehicle_id']);

        if (! $driver->driverProfile) {
            throw ValidationException::withMessages(['driver_id' => ['User bukan driver.']]);
        }

        if ($driver->driverProfile->verification_status !== VerificationStatus::APPROVED) {
            throw ValidationException::withMessages(['driver_id' => ['Driver belum disetujui.']]);
        }

        if ($driver->driverProfile->status !== DriverStatus::AVAILABLE) {
            throw ValidationException::withMessages(['driver_id' => ['Driver sedang tidak tersedia.']]);
        }

        if ($vehicle->verification_status !== VerificationStatus::APPROVED) {
            throw ValidationException::withMessages(['vehicle_id' => ['Kendaraan belum disetujui.']]);
        }

        if ($vehicle->status !== VehicleStatus::AVAILABLE) {
            throw ValidationException::withMessages(['vehicle_id' => ['Kendaraan sedang tidak tersedia.']]);
        }

        if ($vehicle->driver_profile_id !== $driver->driverProfile->id) {
            throw ValidationException::withMessages(['vehicle_id' => ['Kendaraan tidak dimiliki oleh driver yang dipilih.']]);
        }

        $assignment = DB::transaction(function () use ($request, $booking, $driver, $vehicle): DriverAssignment {
            return DriverAssignment::query()->updateOrCreate(
                [
                    'booking_id' => $booking->id,
                    'driver_id' => $driver->id,
                ],
                [
                    'vehicle_id' => $vehicle->id,
                    'offered_by' => $request->user()->id,
                    'status' => DriverAssignmentStatus::OFFERED,
                    'offered_at' => now(),
                    'responded_at' => null,
                    'rejection_reason' => null,
                ],
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Assignment driver berhasil dibuat.',
            'data' => $assignment->load(['driver.driverProfile', 'vehicle', 'offeredBy']),
        ], Response::HTTP_CREATED);
    }

    public function cancel(Booking $booking, DriverAssignment $driverAssignment): JsonResponse
    {
        abort_unless($driverAssignment->booking_id === $booking->id, Response::HTTP_NOT_FOUND);

        if ($driverAssignment->status === DriverAssignmentStatus::CANCELLED) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment sudah dibatalkan.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $driverAssignment->update([
            'status' => DriverAssignmentStatus::CANCELLED,
            'responded_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Assignment driver berhasil dibatalkan.',
            'data' => $driverAssignment->refresh()->load(['driver.driverProfile', 'vehicle']),
        ]);
    }
}
