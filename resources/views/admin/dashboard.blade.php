@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<header class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
    <div>
        <p class="text-sm font-semibold text-amber-600">Operations overview</p>
        <h1 class="mt-1 text-3xl font-black tracking-tight sm:text-4xl">Dashboard</h1>
        <p class="mt-2 text-slate-500">Ringkasan performa dan antrean operasional.</p>
    </div>
    <form method="GET" action="{{ route('admin.dashboard') }}" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_1fr_auto]">
        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dari<input type="date" name="date_from" value="{{ $from->toDateString() }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sampai<input type="date" name="date_to" value="{{ $to->toDateString() }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
        <button class="self-end rounded-lg bg-slate-950 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-800">Terapkan</button>
    </form>
</header>

<section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <a href="{{ route('admin.bookings.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"><p class="text-sm text-slate-500">Total booking</p><p class="mt-3 text-3xl font-black">{{ number_format($metrics['bookings']['total']) }}</p><p class="mt-2 text-xs text-slate-400">{{ number_format($metrics['bookings']['participants']) }} peserta</p></a>
    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Nilai booking</p><p class="mt-3 text-3xl font-black">Rp{{ number_format((float) $metrics['bookings']['value'], 0, ',', '.') }}</p><p class="mt-2 text-xs text-slate-400">Di luar booking batal</p></article>
    <a href="{{ route('admin.payments.index', ['status' => 'pending']) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"><p class="text-sm text-slate-500">Pembayaran masuk</p><p class="mt-3 text-3xl font-black">Rp{{ number_format((float) $metrics['payments']['paid'], 0, ',', '.') }}</p><p class="mt-2 text-xs font-bold text-amber-700">Pending Rp{{ number_format((float) $metrics['payments']['pending'], 0, ',', '.') }} →</p></a>
    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Butuh tindakan</p><p class="mt-3 text-3xl font-black">{{ $metrics['operations']['drivers_pending'] + $metrics['operations']['vehicles_pending'] + $metrics['operations']['withdrawals_pending'] }}</p><p class="mt-2 text-xs text-slate-400">Verifikasi dan withdrawal</p></article>
</section>

<section class="mt-8 grid gap-6 xl:grid-cols-[1fr_320px]">
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><div><h2 class="font-bold">Booking terbaru</h2><p class="text-sm text-slate-500">Delapan transaksi terakhir</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $recentBookings->count() }} data</span></div>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Booking</th><th class="px-5 py-3">Customer</th><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Nilai</th><th class="px-5 py-3">Status</th></tr></thead><tbody class="divide-y divide-slate-100">
        @forelse($recentBookings as $booking)
            <tr class="hover:bg-slate-50"><td class="px-5 py-4"><a href="{{ route('admin.bookings.show', $booking->id) }}" class="font-bold text-amber-700">{{ $booking->booking_code }}</a><p class="text-xs text-slate-500">{{ $booking->tour_package }}</p></td><td class="px-5 py-4">{{ $booking->customer_name }}</td><td class="px-5 py-4">{{ \Carbon\Carbon::parse($booking->tour_date)->format('d M Y') }}</td><td class="px-5 py-4 font-semibold">Rp{{ number_format((float) $booking->total_amount, 0, ',', '.') }}</td><td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold">{{ $booking->status }}</span></td></tr>
        @empty
            <tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">Belum ada booking.</td></tr>
        @endforelse
        </tbody></table></div>
    </div>

    <div class="space-y-4">
        <article class="rounded-2xl bg-slate-950 p-6 text-white shadow-sm"><p class="text-sm text-slate-400">Antrean operasional</p><div class="mt-5 space-y-3">
            <a href="{{ route('admin.drivers.index', ['verification_status' => 'pending']) }}" class="flex justify-between rounded-lg px-2 py-2 hover:bg-white/10"><span>Driver pending</span><strong>{{ $metrics['operations']['drivers_pending'] }}</strong></a>
            <a href="{{ route('admin.vehicles.index', ['status' => 'pending_verification']) }}" class="flex justify-between rounded-lg px-2 py-2 hover:bg-white/10"><span>Vehicle pending</span><strong>{{ $metrics['operations']['vehicles_pending'] }}</strong></a>
            <a href="{{ route('admin.withdrawals.index', ['status' => 'pending']) }}" class="flex justify-between rounded-lg px-2 py-2 hover:bg-white/10"><span>Withdrawal aktif</span><strong>{{ $metrics['operations']['withdrawals_pending'] }}</strong></a>
            <a href="{{ route('admin.bookings.index', ['status' => 'pending']) }}" class="flex justify-between border-t border-white/10 px-2 pt-4"><span>Booking pending</span><strong class="text-amber-400">{{ $metrics['bookings']['pending'] }}</strong></a>
        </div></article>
        <a href="{{ route('admin.reports.index') }}" class="block rounded-2xl border border-amber-200 bg-amber-50 p-5"><p class="font-bold text-amber-900">Reporting center</p><p class="mt-2 text-sm leading-6 text-amber-800">Unduh laporan booking, payment, driver, dan withdrawal.</p></a>
    </div>
</section>
@endsection