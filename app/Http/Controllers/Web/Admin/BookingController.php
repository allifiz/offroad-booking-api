<?php

namespace App\Http\Controllers\Web\Admin;

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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $bookings = Booking::query()->with(['customer', 'tourPackage'])
            ->withCount(['participants', 'driverAssignments'])
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validated['payment_status'] ?? null, fn ($query, $status) => $query->where('payment_status', $status))
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('booking_code', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })->latest()->paginate(15)->withQueryString();

        return view('admin.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking): View
    {
        $booking->load(['customer', 'tourPackage', 'participants', 'driverAssignments.driver.driverProfile', 'driverAssignments.vehicle', 'driverAssignments.offeredBy']);
        $drivers = User::query()
            ->whereHas('driverProfile', fn ($query) => $query->where('verification_status', VerificationStatus::APPROVED->value)->where('status', DriverStatus::AVAILABLE->value))
            ->with(['driverProfile.vehicles' => fn ($query) => $query->where('verification_status', VerificationStatus::APPROVED->value)->where('status', VehicleStatus::AVAILABLE->value)])
            ->orderBy('name')->get();

        return view('admin.bookings.show', compact('booking', 'drivers'));
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate(['status' => ['required', Rule::enum(BookingStatus::class)]]);
        $next = BookingStatus::from($validated['status']);
        $allowed = [
            BookingStatus::PENDING->value => [BookingStatus::CONFIRMED, BookingStatus::CANCELLED],
            BookingStatus::CONFIRMED->value => [BookingStatus::ONGOING, BookingStatus::CANCELLED],
            BookingStatus::ONGOING->value => [],
            BookingStatus::COMPLETED->value => [],
            BookingStatus::CANCELLED->value => [],
        ];

        if (! in_array($next, $allowed[$booking->status->value], true)) {
            throw ValidationException::withMessages(['status' => ['Transisi status booking tidak diizinkan dari panel web. Penyelesaian trip tetap melalui flow API agar reward poin diproses idempotent.']]);
        }
        if ($next === BookingStatus::CONFIRMED && $booking->payment_status !== PaymentStatus::PAID) {
            throw ValidationException::withMessages(['status' => ['Booking harus sudah dibayar sebelum dikonfirmasi.']]);
        }
        if ($next === BookingStatus::ONGOING && ! $booking->driverAssignments()->where('status', DriverAssignmentStatus::ACCEPTED->value)->exists()) {
            throw ValidationException::withMessages(['status' => ['Minimal satu assignment harus sudah diterima driver.']]);
        }

        DB::transaction(function () use ($booking, $next): void {
            $locked = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            $locked->update(['status' => $next]);
            if ($next === BookingStatus::CANCELLED) {
                $locked->driverAssignments()->whereIn('status', [DriverAssignmentStatus::OFFERED->value, DriverAssignmentStatus::ACCEPTED->value])
                    ->update(['status' => DriverAssignmentStatus::CANCELLED->value, 'responded_at' => now()]);
            }
        });

        return back()->with('success', 'Status booking berhasil diperbarui.');
    }

    public function assign(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate(['driver_id' => ['required', 'integer', 'exists:users,id'], 'vehicle_id' => ['required', 'integer', 'exists:vehicles,id']]);
        if (in_array($booking->status, [BookingStatus::CANCELLED, BookingStatus::COMPLETED], true)) {
            throw ValidationException::withMessages(['driver_id' => ['Booking final tidak dapat diberi assignment.']]);
        }
        if ($booking->payment_status !== PaymentStatus::PAID) {
            throw ValidationException::withMessages(['driver_id' => ['Booking harus sudah dibayar sebelum assignment dibuat.']]);
        }

        $driver = User::query()->with('driverProfile')->findOrFail($validated['driver_id']);
        $vehicle = Vehicle::query()->findOrFail($validated['vehicle_id']);
        if (! $driver->driverProfile || $driver->driverProfile->verification_status !== VerificationStatus::APPROVED
            || $driver->driverProfile->status !== DriverStatus::AVAILABLE || $vehicle->driver_profile_id !== $driver->driverProfile->id
            || $vehicle->verification_status !== VerificationStatus::APPROVED || $vehicle->status !== VehicleStatus::AVAILABLE) {
            throw ValidationException::withMessages(['driver_id' => ['Driver atau kendaraan tidak memenuhi syarat assignment.']]);
        }

        DriverAssignment::query()->updateOrCreate(
            ['booking_id' => $booking->id, 'driver_id' => $driver->id],
            ['vehicle_id' => $vehicle->id, 'offered_by' => $request->user()->id, 'status' => DriverAssignmentStatus::OFFERED,
                'offered_at' => now(), 'responded_at' => null, 'rejection_reason' => null],
        );

        return back()->with('success', 'Assignment driver berhasil ditawarkan.');
    }

    public function cancelAssignment(Booking $booking, DriverAssignment $assignment): RedirectResponse
    {
        abort_unless($assignment->booking_id === $booking->id, 404);
        $assignment->update(['status' => DriverAssignmentStatus::CANCELLED, 'responded_at' => now()]);

        return back()->with('success', 'Assignment berhasil dibatalkan.');
    }
}
