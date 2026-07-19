<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\BookingStatus;
use App\Enums\DriverAssignmentStatus;
use App\Enums\TravelGroupSource;
use App\Enums\TravelGroupStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingParticipant;
use App\Models\BookingParticipantVehicleAllocation;
use App\Models\DriverAssignment;
use App\Models\TravelGroup;
use App\Models\TravelGroupMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class TravelGroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(TravelGroupStatus::class)],
            'source' => ['nullable', Rule::enum(TravelGroupSource::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $groups = TravelGroup::query()
            ->with(['leader', 'creator', 'members.user', 'bookings'])
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validated['source'] ?? null, fn ($query, $source) => $query->where('source', $source))
            ->latest()
            ->paginate($validated['per_page'] ?? 10)
            ->withQueryString();

        return response()->json(['success' => true, 'data' => $groups]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'source' => ['required', Rule::enum(TravelGroupSource::class)],
            'leader_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'member_limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'member_user_ids' => ['nullable', 'array'],
            'member_user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $memberIds = collect($validated['member_user_ids'] ?? []);
        if (! empty($validated['leader_user_id'])) {
            $memberIds->push($validated['leader_user_id']);
        }
        $memberIds = $memberIds->unique()->values();

        if (($validated['member_limit'] ?? null) !== null && $memberIds->count() > $validated['member_limit']) {
            throw ValidationException::withMessages([
                'member_user_ids' => ['Jumlah anggota melebihi member_limit travel group.'],
            ]);
        }

        $group = DB::transaction(function () use ($request, $validated, $memberIds): TravelGroup {
            $group = TravelGroup::query()->create([
                'name' => $validated['name'],
                'source' => $validated['source'],
                'leader_user_id' => $validated['leader_user_id'] ?? null,
                'created_by' => $request->user()->id,
                'status' => TravelGroupStatus::DRAFT,
                'member_limit' => $validated['member_limit'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($memberIds as $userId) {
                TravelGroupMember::query()->create([
                    'travel_group_id' => $group->id,
                    'user_id' => $userId,
                    'is_leader' => $userId === ($validated['leader_user_id'] ?? null),
                    'joined_at' => now(),
                ]);
            }

            return $group;
        });

        return response()->json([
            'success' => true,
            'message' => 'Travel group berhasil dibuat.',
            'data' => $group->load(['leader', 'creator', 'members.user']),
        ], Response::HTTP_CREATED);
    }

    public function show(TravelGroup $travelGroup): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $travelGroup->load(['leader', 'creator', 'members.user', 'bookings.participants']),
        ]);
    }

    public function attachBooking(Request $request, TravelGroup $travelGroup): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
        ]);

        $booking = Booking::query()->findOrFail($validated['booking_id']);

        if (in_array($booking->status, [BookingStatus::ONGOING, BookingStatus::COMPLETED, BookingStatus::CANCELLED], true)) {
            throw ValidationException::withMessages([
                'booking_id' => ['Booking ongoing, completed, atau cancelled tidak dapat dipindahkan ke travel group.'],
            ]);
        }

        $currentParticipants = $travelGroup->bookings()->sum('participant_count');
        $limit = $travelGroup->member_limit;
        if ($limit !== null && ($currentParticipants + $booking->participant_count) > $limit) {
            throw ValidationException::withMessages([
                'booking_id' => ['Total peserta booking melebihi member_limit travel group.'],
            ]);
        }

        $booking->update(['travel_group_id' => $travelGroup->id]);

        return response()->json([
            'success' => true,
            'message' => 'Booking berhasil dimasukkan ke travel group.',
            'data' => $travelGroup->fresh()->load(['bookings.participants', 'members.user']),
        ]);
    }

    public function allocateParticipant(Request $request, Booking $booking): JsonResponse
    {
        $validated = $request->validate([
            'booking_participant_id' => ['required', 'integer', 'exists:booking_participants,id'],
            'driver_assignment_id' => ['required', 'integer', 'exists:driver_assignments,id'],
        ]);

        if (in_array($booking->status, [BookingStatus::COMPLETED, BookingStatus::CANCELLED], true)) {
            throw ValidationException::withMessages([
                'booking' => ['Peserta booking final tidak dapat dialokasikan.'],
            ]);
        }

        $participant = BookingParticipant::query()->findOrFail($validated['booking_participant_id']);
        $assignment = DriverAssignment::query()->with('vehicle')->findOrFail($validated['driver_assignment_id']);

        abort_unless($participant->booking_id === $booking->id, Response::HTTP_NOT_FOUND);
        abort_unless($assignment->booking_id === $booking->id, Response::HTTP_NOT_FOUND);

        if ($assignment->status !== DriverAssignmentStatus::ACCEPTED) {
            throw ValidationException::withMessages([
                'driver_assignment_id' => ['Hanya assignment accepted yang dapat menerima alokasi peserta.'],
            ]);
        }

        $allocatedCount = BookingParticipantVehicleAllocation::query()
            ->where('booking_id', $booking->id)
            ->where('driver_assignment_id', $assignment->id)
            ->where('booking_participant_id', '!=', $participant->id)
            ->count();

        if ($allocatedCount >= $assignment->vehicle->capacity) {
            throw ValidationException::withMessages([
                'driver_assignment_id' => ['Kapasitas kendaraan sudah penuh.'],
            ]);
        }

        $allocation = BookingParticipantVehicleAllocation::query()->updateOrCreate(
            ['booking_participant_id' => $participant->id],
            [
                'booking_id' => $booking->id,
                'driver_assignment_id' => $assignment->id,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Peserta berhasil dialokasikan ke kendaraan.',
            'data' => $allocation->load(['participant', 'driverAssignment.driver', 'driverAssignment.vehicle']),
        ]);
    }

    public function allocations(Booking $booking): JsonResponse
    {
        $allocations = BookingParticipantVehicleAllocation::query()
            ->where('booking_id', $booking->id)
            ->with(['participant', 'driverAssignment.driver', 'driverAssignment.vehicle'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'booking' => $booking->load(['participants', 'travelGroup']),
                'allocations' => $allocations,
                'unallocated_participants' => $booking->participants()
                    ->whereDoesntHave('vehicleAllocation')
                    ->get(),
            ],
        ]);
    }
}
