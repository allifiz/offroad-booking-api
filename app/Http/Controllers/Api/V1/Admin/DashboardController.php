<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $to = isset($validated['date_to'])
            ? CarbonImmutable::parse($validated['date_to'])->endOfDay()
            : CarbonImmutable::now()->endOfDay();
        $from = isset($validated['date_from'])
            ? CarbonImmutable::parse($validated['date_from'])->startOfDay()
            : $to->subDays(29)->startOfDay();

        if ($from->diffInDays($to) > 366) {
            throw ValidationException::withMessages([
                'date_from' => ['Rentang laporan maksimal 366 hari.'],
            ]);
        }

        $bookingBase = DB::table('bookings')->whereBetween('created_at', [$from, $to]);
        $paymentBase = DB::table('payments')->whereBetween('created_at', [$from, $to]);
        $withdrawalBase = DB::table('withdrawals')->whereBetween('created_at', [$from, $to]);

        $bookingStatuses = $this->countsByStatus(clone $bookingBase, [
            'pending', 'confirmed', 'ongoing', 'completed', 'cancelled',
        ]);
        $paymentStatuses = $this->countsByStatus(clone $paymentBase, [
            'unpaid', 'pending', 'paid', 'refunded', 'failed',
        ]);
        $withdrawalStatuses = $this->countsByStatus(clone $withdrawalBase, [
            'pending', 'approved', 'rejected', 'paid',
        ]);

        $bookingTrend = (clone $bookingBase)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as bookings, COALESCE(SUM(total_amount), 0) as gross_booking_value')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $paymentTrend = (clone $paymentBase)
            ->where('status', 'paid')
            ->selectRaw('DATE(created_at) as date, COALESCE(SUM(amount), 0) as paid_revenue')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $trend = collect();
        for ($date = $from->startOfDay(); $date->lte($to); $date = $date->addDay()) {
            $key = $date->toDateString();
            $trend->push([
                'date' => $key,
                'bookings' => (int) ($bookingTrend[$key]->bookings ?? 0),
                'gross_booking_value' => (float) ($bookingTrend[$key]->gross_booking_value ?? 0),
                'paid_revenue' => (float) ($paymentTrend[$key]->paid_revenue ?? 0),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'date_from' => $from->toDateString(),
                    'date_to' => $to->toDateString(),
                    'days' => (int) $from->diffInDays($to) + 1,
                ],
                'bookings' => [
                    'total' => array_sum($bookingStatuses),
                    'by_status' => $bookingStatuses,
                    'participants' => (int) (clone $bookingBase)->sum('participant_count'),
                    'gross_booking_value' => (float) (clone $bookingBase)
                        ->where('status', '!=', 'cancelled')
                        ->sum('total_amount'),
                ],
                'payments' => [
                    'total' => array_sum($paymentStatuses),
                    'by_status' => $paymentStatuses,
                    'paid_revenue' => (float) (clone $paymentBase)
                        ->where('status', 'paid')
                        ->sum('amount'),
                    'pending_amount' => (float) (clone $paymentBase)
                        ->where('status', 'pending')
                        ->sum('amount'),
                    'refunded_amount' => (float) (clone $paymentBase)
                        ->where('status', 'refunded')
                        ->sum('amount'),
                ],
                'drivers' => [
                    'total' => DB::table('driver_profiles')->count(),
                    'approved' => DB::table('driver_profiles')->where('verification_status', 'approved')->count(),
                    'pending_verification' => DB::table('driver_profiles')->where('verification_status', 'pending')->count(),
                    'available' => DB::table('driver_profiles')->where('status', 'available')->count(),
                    'available_points' => (int) DB::table('driver_profiles')->sum('available_points'),
                    'held_points' => (int) DB::table('driver_profiles')->sum('held_points'),
                ],
                'vehicles' => [
                    'total' => DB::table('vehicles')->count(),
                    'approved' => DB::table('vehicles')->where('verification_status', 'approved')->count(),
                    'pending_verification' => DB::table('vehicles')->where('verification_status', 'pending')->count(),
                    'available' => DB::table('vehicles')->where('status', 'available')->count(),
                ],
                'withdrawals' => [
                    'total' => array_sum($withdrawalStatuses),
                    'by_status' => $withdrawalStatuses,
                    'requested_points' => (int) (clone $withdrawalBase)->sum('points'),
                    'paid_amount' => (float) (clone $withdrawalBase)->where('status', 'paid')->sum('amount'),
                    'pending_amount' => (float) (clone $withdrawalBase)->whereIn('status', ['pending', 'approved'])->sum('amount'),
                ],
                'trend' => $trend,
            ],
        ]);
    }

    private function countsByStatus(Builder $query, array $statuses): array
    {
        $counts = $query
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return collect($statuses)
            ->mapWithKeys(fn (string $status): array => [$status => (int) ($counts[$status] ?? 0)])
            ->all();
    }
}
