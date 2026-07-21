<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\BookingStatus;
use App\Enums\TravelGroupSource;
use App\Enums\TravelGroupStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\TravelGroup;
use App\Models\TravelGroupMember;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TravelGroupController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(TravelGroupStatus::class)],
            'source' => ['nullable', Rule::enum(TravelGroupSource::class)],
        ]);

        $travelGroups = TravelGroup::query()
            ->with(['leader', 'creator'])
            ->withCount(['members', 'bookings'])
            ->when($validated['search'] ?? null, fn ($query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['source'] ?? null, fn ($query, string $source) => $query->where('source', $source))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.travel-groups.index', compact('travelGroups'));
    }

    public function create(): View
    {
        return view('admin.travel-groups.form', [
            'travelGroup' => new TravelGroup(),
            'users' => $this->eligibleUsers(),
            'sources' => TravelGroupSource::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
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
            throw ValidationException::withMessages(['member_user_ids' => ['Jumlah anggota melebihi batas travel group.']]);
        }

        $travelGroup = DB::transaction(function () use ($request, $validated, $memberIds): TravelGroup {
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

        return redirect()->route('admin.travel-groups.show', $travelGroup)->with('success', 'Travel group berhasil dibuat.');
    }

    public function show(TravelGroup $travelGroup): View
    {
        $travelGroup->load(['leader', 'creator', 'members.user', 'bookings.customer', 'bookings.tourPackage']);

        $candidateBookings = Booking::query()
            ->with(['customer', 'tourPackage'])
            ->whereNull('travel_group_id')
            ->whereNotIn('status', [BookingStatus::ONGOING, BookingStatus::COMPLETED, BookingStatus::CANCELLED])
            ->latest()
            ->limit(100)
            ->get();

        return view('admin.travel-groups.show', [
            'travelGroup' => $travelGroup,
            'candidateBookings' => $candidateBookings,
            'statuses' => TravelGroupStatus::cases(),
        ]);
    }

    public function updateStatus(Request $request, TravelGroup $travelGroup): RedirectResponse
    {
        $validated = $request->validate(['status' => ['required', Rule::enum(TravelGroupStatus::class)]]);
        $travelGroup->update(['status' => $validated['status']]);

        return back()->with('success', 'Status travel group berhasil diperbarui.');
    }

    public function attachBooking(Request $request, TravelGroup $travelGroup): RedirectResponse
    {
        $validated = $request->validate(['booking_id' => ['required', 'integer', 'exists:bookings,id']]);
        $booking = Booking::query()->findOrFail($validated['booking_id']);

        if ($booking->travel_group_id !== null || in_array($booking->status, [BookingStatus::ONGOING, BookingStatus::COMPLETED, BookingStatus::CANCELLED], true)) {
            throw ValidationException::withMessages(['booking_id' => ['Booking tidak dapat dimasukkan ke travel group.']]);
        }

        $currentParticipants = (int) $travelGroup->bookings()->sum('participant_count');
        if ($travelGroup->member_limit !== null && $currentParticipants + $booking->participant_count > $travelGroup->member_limit) {
            throw ValidationException::withMessages(['booking_id' => ['Total peserta melebihi batas travel group.']]);
        }

        $booking->update(['travel_group_id' => $travelGroup->id]);

        return back()->with('success', 'Booking berhasil dimasukkan ke travel group.');
    }

    private function eligibleUsers()
    {
        return User::query()
            ->whereIn('role', [UserRole::CUSTOMER->value, UserRole::DRIVER->value])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);
    }
}
