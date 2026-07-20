<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function bookings(Request $request): StreamedResponse
    {
        $filters = $this->validatePeriodAndStatus($request, [
            'pending', 'confirmed', 'ongoing', 'completed', 'cancelled',
        ]);

        $query = DB::table('bookings')
            ->leftJoin('users as customers', 'customers.id', '=', 'bookings.customer_id')
            ->leftJoin('tour_packages', 'tour_packages.id', '=', 'bookings.tour_package_id')
            ->select([
                'bookings.booking_code',
                'customers.name as customer_name',
                'customers.email as customer_email',
                'tour_packages.name as tour_package',
                'bookings.tour_date',
                'bookings.participant_count',
                'bookings.total_amount',
                'bookings.status',
                'bookings.payment_status',
                'bookings.created_at',
            ]);

        $this->applyFilters($query, $filters);

        return $this->streamCsv('bookings', [
            'Booking Code', 'Customer', 'Customer Email', 'Tour Package', 'Tour Date',
            'Participants', 'Total Amount', 'Booking Status', 'Payment Status', 'Created At',
        ], $query, fn (object $row): array => [
            $row->booking_code,
            $row->customer_name,
            $row->customer_email,
            $row->tour_package,
            $row->tour_date,
            $row->participant_count,
            $row->total_amount,
            $row->status,
            $row->payment_status,
            $row->created_at,
        ]);
    }

    public function payments(Request $request): StreamedResponse
    {
        $filters = $this->validatePeriodAndStatus($request, [
            'unpaid', 'pending', 'paid', 'refunded', 'failed',
        ]);

        $query = DB::table('payments')
            ->leftJoin('bookings', 'bookings.id', '=', 'payments.booking_id')
            ->leftJoin('users as customers', 'customers.id', '=', 'payments.customer_id')
            ->leftJoin('users as reviewers', 'reviewers.id', '=', 'payments.reviewed_by')
            ->select([
                'payments.id',
                'bookings.booking_code',
                'customers.name as customer_name',
                'payments.amount',
                'payments.method',
                'payments.status',
                'payments.submitted_at',
                'reviewers.name as reviewer_name',
                'payments.reviewed_at',
                'payments.rejection_reason',
                'payments.created_at',
            ]);

        $this->applyFilters($query, $filters, 'payments');

        return $this->streamCsv('payments', [
            'Payment ID', 'Booking Code', 'Customer', 'Amount', 'Method', 'Status',
            'Submitted At', 'Reviewer', 'Reviewed At', 'Rejection Reason', 'Created At',
        ], $query, fn (object $row): array => [
            $row->id,
            $row->booking_code,
            $row->customer_name,
            $row->amount,
            $row->method,
            $row->status,
            $row->submitted_at,
            $row->reviewer_name,
            $row->reviewed_at,
            $row->rejection_reason,
            $row->created_at,
        ]);
    }

    public function drivers(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'verification_status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'status' => ['nullable', Rule::in(['available', 'unavailable', 'suspended', 'inactive'])],
        ]);

        [$from, $to] = $this->resolvePeriod($validated);

        $query = DB::table('driver_profiles')
            ->join('users', 'users.id', '=', 'driver_profiles.user_id')
            ->leftJoin('users as verifiers', 'verifiers.id', '=', 'driver_profiles.verified_by')
            ->whereBetween('driver_profiles.created_at', [$from, $to])
            ->when($validated['verification_status'] ?? null, fn (Builder $query, string $status) => $query->where('driver_profiles.verification_status', $status))
            ->when($validated['status'] ?? null, fn (Builder $query, string $status) => $query->where('driver_profiles.status', $status))
            ->select([
                'driver_profiles.id',
                'users.name',
                'users.email',
                'users.phone',
                'driver_profiles.license_number',
                'driver_profiles.identity_number',
                'driver_profiles.status',
                'driver_profiles.verification_status',
                'driver_profiles.available_points',
                'driver_profiles.held_points',
                'driver_profiles.joined_at',
                'verifiers.name as verifier_name',
                'driver_profiles.verified_at',
                'driver_profiles.created_at',
            ]);

        return $this->streamCsv('drivers', [
            'Driver ID', 'Name', 'Email', 'Phone', 'License Number', 'Identity Number',
            'Status', 'Verification Status', 'Available Points', 'Held Points', 'Joined At',
            'Verifier', 'Verified At', 'Created At',
        ], $query, fn (object $row): array => [
            $row->id,
            $row->name,
            $row->email,
            $row->phone,
            $row->license_number,
            $row->identity_number,
            $row->status,
            $row->verification_status,
            $row->available_points,
            $row->held_points,
            $row->joined_at,
            $row->verifier_name,
            $row->verified_at,
            $row->created_at,
        ]);
    }

    public function withdrawals(Request $request): StreamedResponse
    {
        $filters = $this->validatePeriodAndStatus($request, [
            'pending', 'approved', 'rejected', 'paid',
        ]);

        $query = DB::table('withdrawals')
            ->join('driver_profiles', 'driver_profiles.id', '=', 'withdrawals.driver_profile_id')
            ->join('users as drivers', 'drivers.id', '=', 'driver_profiles.user_id')
            ->leftJoin('users as processors', 'processors.id', '=', 'withdrawals.processed_by')
            ->select([
                'withdrawals.id',
                'drivers.name as driver_name',
                'drivers.email as driver_email',
                'withdrawals.points',
                'withdrawals.amount',
                'withdrawals.status',
                'withdrawals.bank_name',
                'withdrawals.account_number',
                'withdrawals.account_name',
                'withdrawals.requested_at',
                'processors.name as processor_name',
                'withdrawals.processed_at',
                'withdrawals.rejection_reason',
                'withdrawals.created_at',
            ]);

        $this->applyFilters($query, $filters, 'withdrawals');

        return $this->streamCsv('withdrawals', [
            'Withdrawal ID', 'Driver', 'Driver Email', 'Points', 'Amount', 'Status',
            'Bank', 'Account Number', 'Account Name', 'Requested At', 'Processor',
            'Processed At', 'Rejection Reason', 'Created At',
        ], $query, fn (object $row): array => [
            $row->id,
            $row->driver_name,
            $row->driver_email,
            $row->points,
            $row->amount,
            $row->status,
            $row->bank_name,
            $row->account_number,
            $row->account_name,
            $row->requested_at,
            $row->processor_name,
            $row->processed_at,
            $row->rejection_reason,
            $row->created_at,
        ]);
    }

    private function validatePeriodAndStatus(Request $request, array $statuses): array
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', Rule::in($statuses)],
        ]);

        [$validated['from'], $validated['to']] = $this->resolvePeriod($validated);

        return $validated;
    }

    private function resolvePeriod(array $validated): array
    {
        $to = isset($validated['date_to'])
            ? CarbonImmutable::parse($validated['date_to'])->endOfDay()
            : CarbonImmutable::now()->endOfDay();
        $from = isset($validated['date_from'])
            ? CarbonImmutable::parse($validated['date_from'])->startOfDay()
            : $to->subDays(29)->startOfDay();

        abort_if($from->diffInDays($to) > 366, 422, 'Rentang export maksimal 366 hari.');

        return [$from, $to];
    }

    private function applyFilters(Builder $query, array $filters, string $table = 'bookings'): void
    {
        $query
            ->whereBetween($table.'.created_at', [$filters['from'], $filters['to']])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where($table.'.status', $status));
    }

    private function streamCsv(string $prefix, array $headers, Builder $query, callable $map): StreamedResponse
    {
        $filename = sprintf('%s-%s.csv', $prefix, now()->format('Ymd-His'));

        return response()->streamDownload(function () use ($headers, $query, $map): void {
            $stream = fopen('php://output', 'wb');

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $headers);

            foreach ($query->orderBy($query->from.'.id')->cursor() as $row) {
                fputcsv($stream, array_map([$this, 'sanitizeCsvValue'], $map($row)));
            }

            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function sanitizeCsvValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}
