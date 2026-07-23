<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverStatus;
use App\Enums\VehicleStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\DriverAssignment;
use App\Services\BookingLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class DriverAssignmentController extends Controller
{
    public function __construct(private readonly BookingLifecycleService $bookingLifecycle) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:'.implode(',', array_column(DriverAssignmentStatus::cases(), 'value'))],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $assignments = DriverAssignment::query()
            ->with(['booking.tourPackage', 'booking.participants', 'vehicle', 'offeredBy'])
            ->where('driver_id', $request->user()->id)
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('offered_at')
            ->paginate($validated['per_page'] ?? 10)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $assignments,
        ]);
    }

    public function show(Request $request, DriverAssignment $driverAssignment): JsonResponse
    {
        $this->ensureOwnership($request, $driverAssignment);

        return response()->json([
            'success' => true,
            'data' => $driverAssignment->load([
                'booking.customer',
                'booking.tourPackage',
                'booking.participants',
                'vehicle',
                'offeredBy',
            ]),
        ]);
    }

    public function accept(Request $request, DriverAssignment $driverAssignment): JsonResponse
    {
        $this->ensureOwnership($request, $driverAssignment);
        $this->ensureOffered($driverAssignment);

        $driverAssignment->load(['booking', 'driver.driverProfile', 'vehicle']);

        if (in_array($driverAssignment->booking->status, [BookingStatus::CANCELLED, BookingStatus::COMPLETED], true)) {
            throw ValidationException::withMessages([
                'assignment' => ['Booking sudah selesai atau dibatalkan.'],
            ]);
        }

        $driverProfile = $driverAssignment->driver->driverProfile;

        if (! $driverProfile || $driverProfile->verification_status !== VerificationStatus::APPROVED) {
            throw ValidationException::withMessages([
                'driver' => ['Driver belum disetujui.'],
            ]);
        }

        if ($driverProfile->status !== DriverStatus::AVAILABLE) {
            throw ValidationException::withMessages([
                'driver' => ['Driver sedang tidak tersedia.'],
            ]);
        }

        if ($driverAssignment->vehicle->verification_status !== VerificationStatus::APPROVED) {
            throw ValidationException::withMessages([
                'vehicle' => ['Kendaraan belum disetujui.'],
            ]);
        }

        if ($driverAssignment->vehicle->status !== VehicleStatus::AVAILABLE) {
            throw ValidationException::withMessages([
                'vehicle' => ['Kendaraan sedang tidak tersedia.'],
            ]);
        }

        $conflict = DriverAssignment::query()
            ->whereKeyNot($driverAssignment->id)
            ->where('status', DriverAssignmentStatus::ACCEPTED)
            ->where(function ($query) use ($driverAssignment): void {
                $query->where('driver_id', $driverAssignment->driver_id)
                    ->orWhere('vehicle_id', $driverAssignment->vehicle_id);
            })
            ->whereHas('booking', fn ($query) => $query
                ->whereDate('tour_date', $driverAssignment->booking->tour_date)
                ->whereNotIn('status', [BookingStatus::CANCELLED, BookingStatus::COMPLETED]))
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'assignment' => ['Driver atau kendaraan sudah memiliki assignment accepted pada tanggal tersebut.'],
            ]);
        }

        $driverAssignment->update([
            'status' => DriverAssignmentStatus::ACCEPTED,
            'responded_at' => now(),
            'rejection_reason' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Assignment berhasil diterima.',
            'data' => $driverAssignment->refresh()->load(['booking.tourPackage', 'vehicle']),
        ]);
    }

    public function reject(Request $request, DriverAssignment $driverAssignment): JsonResponse
    {
        $this->ensureOwnership($request, $driverAssignment);
        $this->ensureOffered($driverAssignment);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $driverAssignment->update([
            'status' => DriverAssignmentStatus::REJECTED,
            'responded_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Assignment berhasil ditolak.',
            'data' => $driverAssignment->refresh()->load(['booking.tourPackage', 'vehicle']),
        ]);
    }

    public function startTrip(Request $request, DriverAssignment $driverAssignment): JsonResponse
    {
        $this->ensureOwnedAcceptedAssignment($request, $driverAssignment);
        $this->bookingLifecycle->transition($driverAssignment->booking, BookingStatus::ONGOING);

        return $this->tripResponse(
            $driverAssignment,
            'Perjalanan berhasil dimulai.',
        );
    }

    public function completeTrip(Request $request, DriverAssignment $driverAssignment): JsonResponse
    {
        $this->ensureOwnedAcceptedAssignment($request, $driverAssignment);
        $this->bookingLifecycle->transition($driverAssignment->booking, BookingStatus::COMPLETED);

        return $this->tripResponse(
            $driverAssignment,
            'Perjalanan berhasil diselesaikan.',
        );
    }

    private function ensureOwnership(Request $request, DriverAssignment $driverAssignment): void
    {
        abort_unless($driverAssignment->driver_id === $request->user()->id, Response::HTTP_NOT_FOUND);
    }

    private function ensureOffered(DriverAssignment $driverAssignment): void
    {
        if ($driverAssignment->status !== DriverAssignmentStatus::OFFERED) {
            throw ValidationException::withMessages([
                'assignment' => ['Hanya assignment offered yang dapat direspons.'],
            ]);
        }
    }

    private function ensureOwnedAcceptedAssignment(Request $request, DriverAssignment $driverAssignment): void
    {
        $this->ensureOwnership($request, $driverAssignment);

        if ($driverAssignment->status !== DriverAssignmentStatus::ACCEPTED) {
            throw ValidationException::withMessages([
                'assignment' => ['Hanya assignment accepted yang dapat mengubah status perjalanan.'],
            ]);
        }
    }

    private function tripResponse(DriverAssignment $driverAssignment, string $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $driverAssignment->refresh()->load([
                'booking.customer',
                'booking.tourPackage',
                'booking.participants',
                'vehicle',
                'offeredBy',
            ]),
        ]);
    }
}
