<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
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

        abort_if($from->diffInDays($to) > 366, 422, 'Rentang dashboard maksimal 366 hari.');

        $bookingBase = DB::table('bookings')->whereBetween('created_at', [$from, $to]);
        $paymentBase = DB::table('payments')->whereBetween('created_at', [$from, $to]);
        $withdrawalBase = DB::table('withdrawals')->whereBetween('created_at', [$from, $to]);

        $metrics = [
            'bookings' => [
                'total' => (clone $bookingBase)->count(),
                'participants' => (int) (clone $bookingBase)->sum('participant_count'),
                'value' => (float) (clone $bookingBase)->where('status', '!=', 'cancelled')->sum('total_amount'),
                'pending' => (clone $bookingBase)->where('status', 'pending')->count(),
            ],
            'payments' => [
                'paid' => (float) (clone $paymentBase)->where('status', 'paid')->sum('amount'),
                'pending' => (float) (clone $paymentBase)->where('status', 'pending')->sum('amount'),
            ],
            'operations' => [
                'drivers_pending' => DB::table('driver_profiles')->where('verification_status', 'pending')->count(),
                'vehicles_pending' => DB::table('vehicles')->where('verification_status', 'pending')->count(),
                'withdrawals_pending' => (clone $withdrawalBase)->whereIn('status', ['pending', 'approved'])->count(),
            ],
        ];

        $recentBookings = DB::table('bookings')
            ->leftJoin('users as customers', 'customers.id', '=', 'bookings.customer_id')
            ->leftJoin('tour_packages', 'tour_packages.id', '=', 'bookings.tour_package_id')
            ->select('bookings.id', 'bookings.booking_code', 'customers.name as customer_name', 'tour_packages.name as tour_package', 'bookings.tour_date', 'bookings.total_amount', 'bookings.status', 'bookings.payment_status')
            ->latest('bookings.created_at')
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact('from', 'to', 'metrics', 'recentBookings'));
    }
}
