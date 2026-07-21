@extends('layouts.admin')

@section('title', 'Bookings')

@section('content')
<div>
    <p class="text-xs font-bold uppercase tracking-[.2em] text-amber-600">Operations</p>
    <h1 class="mt-2 text-3xl font-black">Bookings</h1>
    <p class="mt-2 text-slate-600">Kelola status booking, pembayaran, dan assignment driver.</p>
</div>

<form method="GET" class="mt-6 grid gap-3 rounded-2xl border bg-white p-4 shadow-sm md:grid-cols-4">
    <input name="search" value="{{ request('search') }}" placeholder="Booking/customer" class="rounded-xl border-slate-300">
    <select name="status" class="rounded-xl border-slate-300">
        <option value="">Semua status</option>
        @foreach (\App\Enums\BookingStatus::cases() as $status)
            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst($status->value) }}</option>
        @endforeach
    </select>
    <select name="payment_status" class="rounded-xl border-slate-300">
        <option value="">Semua pembayaran</option>
        @foreach (\App\Enums\PaymentStatus::cases() as $status)
            <option value="{{ $status->value }}" @selected(request('payment_status') === $status->value)>{{ ucfirst($status->value) }}</option>
        @endforeach
    </select>
    <button class="rounded-xl bg-slate-950 px-5 py-3 font-bold text-white">Filter</button>
</form>

<div class="mt-6 overflow-hidden rounded-2xl border bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr><th class="px-5 py-3">Booking</th><th class="px-5 py-3">Customer</th><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Nilai</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($bookings as $booking)
                    <tr>
                        <td class="px-5 py-4"><p class="font-bold">{{ $booking->booking_code }}</p><p class="text-xs text-slate-500">{{ $booking->tourPackage?->name }}</p></td>
                        <td class="px-5 py-4">{{ $booking->customer?->name }}</td>
                        <td class="px-5 py-4">{{ \Carbon\Carbon::parse($booking->tour_date)->format('d M Y') }}</td>
                        <td class="px-5 py-4 font-semibold">Rp{{ number_format((float) $booking->total_amount, 0, ',', '.') }}</td>
                        <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold">{{ $booking->status->value }} / {{ $booking->payment_status->value }}</span></td>
                        <td class="px-5 py-4 text-right"><a class="font-bold text-amber-700" href="{{ route('admin.bookings.show', $booking) }}">Buka</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">Belum ada booking.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t p-4">{{ $bookings->links() }}</div>
</div>
@endsection
