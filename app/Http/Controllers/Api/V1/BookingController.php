<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\TourPackageStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\TourPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:'.implode(',', array_column(BookingStatus::cases(), 'value'))],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $bookings = Booking::query()
            ->with(['tourPackage', 'participants'])
            ->where('customer_id', $request->user()->id)
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate($validated['per_page'] ?? 10)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tour_package_id' => ['required', 'integer', 'exists:tour_packages,id'],
            'tour_date' => ['required', 'date', 'after:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'participants' => ['required', 'array', 'min:1'],
            'participants.*.name' => ['required', 'string', 'max:100'],
            'participants.*.phone' => ['nullable', 'string', 'max:30'],
            'participants.*.is_group_leader' => ['nullable', 'boolean'],
        ]);

        $tourPackage = TourPackage::query()->findOrFail($validated['tour_package_id']);

        if ($tourPackage->status !== TourPackageStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'tour_package_id' => ['Paket wisata tidak aktif.'],
            ]);
        }

        $participantCount = count($validated['participants']);

        if ($participantCount < $tourPackage->minimum_participants || $participantCount > $tourPackage->maximum_participants) {
            throw ValidationException::withMessages([
                'participants' => ["Jumlah peserta harus antara {$tourPackage->minimum_participants} dan {$tourPackage->maximum_participants}."],
            ]);
        }

        $leaderCount = collect($validated['participants'])->where('is_group_leader', true)->count();
        if ($leaderCount > 1) {
            throw ValidationException::withMessages([
                'participants' => ['Hanya boleh ada satu group leader.'],
            ]);
        }

        $booking = DB::transaction(function () use ($request, $validated, $tourPackage, $participantCount, $leaderCount): Booking {
            $booking = Booking::create([
                'booking_code' => $this->generateBookingCode(),
                'customer_id' => $request->user()->id,
                'tour_package_id' => $tourPackage->id,
                'tour_date' => $validated['tour_date'],
                'participant_count' => $participantCount,
                'total_amount' => $participantCount * (float) $tourPackage->price_per_person,
                'status' => BookingStatus::PENDING,
                'payment_status' => PaymentStatus::UNPAID,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['participants'] as $index => $participant) {
                $booking->participants()->create([
                    'user_id' => $index === 0 ? $request->user()->id : null,
                    'name' => $participant['name'],
                    'phone' => $participant['phone'] ?? null,
                    'is_group_leader' => $participant['is_group_leader'] ?? ($leaderCount === 0 && $index === 0),
                ]);
            }

            return $booking->load(['tourPackage', 'participants']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Booking berhasil dibuat.',
            'data' => $booking,
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        abort_unless($booking->customer_id === $request->user()->id, Response::HTTP_NOT_FOUND);

        return response()->json([
            'success' => true,
            'data' => $booking->load(['tourPackage', 'participants']),
        ]);
    }

    private function generateBookingCode(): string
    {
        do {
            $code = 'OB-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Booking::query()->where('booking_code', $code)->exists());

        return $code;
    }
}
