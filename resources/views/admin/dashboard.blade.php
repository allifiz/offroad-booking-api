<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin · Offroad Booking</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<div class="min-h-screen lg:grid lg:grid-cols-[260px_1fr]">
    <aside class="border-b border-slate-800 bg-slate-950 px-6 py-5 text-white lg:min-h-screen lg:border-b-0 lg:border-r">
        <div class="flex items-center justify-between lg:block">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-amber-400">Offroad Booking</p>
                <h1 class="mt-2 text-xl font-black">Admin Panel</h1>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}" class="lg:hidden">@csrf<button class="text-sm text-slate-400 hover:text-white">Keluar</button></form>
        </div>
        <nav class="mt-8 grid grid-cols-2 gap-2 text-sm lg:grid-cols-1">
            <a href="{{ route('admin.dashboard') }}" class="rounded-xl bg-amber-500 px-4 py-3 font-bold text-slate-950">Dashboard</a>
            <span class="rounded-xl px-4 py-3 text-slate-500">Bookings</span>
            <span class="rounded-xl px-4 py-3 text-slate-500">Payments</span>
            <span class="rounded-xl px-4 py-3 text-slate-500">Drivers</span>
            <span class="rounded-xl px-4 py-3 text-slate-500">Vehicles</span>
            <span class="rounded-xl px-4 py-3 text-slate-500">Withdrawals</span>
            <span class="rounded-xl px-4 py-3 text-slate-500">Audit Logs</span>
        </nav>
        <div class="mt-10 hidden border-t border-white/10 pt-6 lg:block">
            <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
            <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
            <form method="POST" action="{{ route('admin.logout') }}" class="mt-4">@csrf<button class="text-sm font-medium text-slate-400 hover:text-white">Keluar</button></form>
        </div>
    </aside>

    <main class="p-5 sm:p-8 lg:p-10">
        <header class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-sm font-semibold text-amber-600">Operations overview</p>
                <h2 class="mt-1 text-3xl font-black tracking-tight sm:text-4xl">Dashboard</h2>
                <p class="mt-2 text-slate-500">Ringkasan performa dan antrean operasional.</p>
            </div>
            <form method="GET" action="{{ route('admin.dashboard') }}" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_1fr_auto]">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dari<input type="date" name="date_from" value="{{ $from->toDateString() }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sampai<input type="date" name="date_to" value="{{ $to->toDateString() }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
                <button class="self-end rounded-lg bg-slate-950 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-800">Terapkan</button>
            </form>
        </header>

        <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Total booking</p><p class="mt-3 text-3xl font-black">{{ number_format($metrics['bookings']['total']) }}</p><p class="mt-2 text-xs text-slate-400">{{ number_format($metrics['bookings']['participants']) }} peserta</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Nilai booking</p><p class="mt-3 text-3xl font-black">Rp{{ number_format($metrics['bookings']['value'], 0, ',', '.') }}</p><p class="mt-2 text-xs text-slate-400">Di luar booking batal</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Pembayaran masuk</p><p class="mt-3 text-3xl font-black">Rp{{ number_format($metrics['payments']['paid'], 0, ',', '.') }}</p><p class="mt-2 text-xs text-amber-600">Pending Rp{{ number_format($metrics['payments']['pending'], 0, ',', '.') }}</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Butuh tindakan</p><p class="mt-3 text-3xl font-black">{{ $metrics['operations']['drivers_pending'] + $metrics['operations']['vehicles_pending'] + $metrics['operations']['withdrawals_pending'] }}</p><p class="mt-2 text-xs text-slate-400">Verifikasi dan withdrawal</p></article>
        </section>

        <section class="mt-8 grid gap-6 xl:grid-cols-[1fr_320px]">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><div><h3 class="font-bold">Booking terbaru</h3><p class="text-sm text-slate-500">Delapan transaksi terakhir</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $recentBookings->count() }} data</span></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Booking</th><th class="px-5 py-3">Customer</th><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Nilai</th><th class="px-5 py-3">Status</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse($recentBookings as $booking)
                            <tr class="hover:bg-slate-50"><td class="px-5 py-4"><p class="font-bold">{{ $booking->booking_code }}</p><p class="text-xs text-slate-500">{{ $booking->tour_package }}</p></td><td class="px-5 py-4">{{ $booking->customer_name }}</td><td class="px-5 py-4">{{ \Carbon\Carbon::parse($booking->tour_date)->format('d M Y') }}</td><td class="px-5 py-4 font-semibold">Rp{{ number_format($booking->total_amount, 0, ',', '.') }}</td><td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold">{{ $booking->status }}</span></td></tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">Belum ada booking.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-4">
                <article class="rounded-2xl bg-slate-950 p-6 text-white shadow-sm"><p class="text-sm text-slate-400">Antrean operasional</p><div class="mt-5 space-y-4"><div class="flex justify-between"><span>Driver pending</span><strong>{{ $metrics['operations']['drivers_pending'] }}</strong></div><div class="flex justify-between"><span>Vehicle pending</span><strong>{{ $metrics['operations']['vehicles_pending'] }}</strong></div><div class="flex justify-between"><span>Withdrawal aktif</span><strong>{{ $metrics['operations']['withdrawals_pending'] }}</strong></div><div class="flex justify-between border-t border-white/10 pt-4"><span>Booking pending</span><strong class="text-amber-400">{{ $metrics['bookings']['pending'] }}</strong></div></div></article>
                <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5"><p class="font-bold text-amber-900">Tahap berikutnya</p><p class="mt-2 text-sm leading-6 text-amber-800">Menu operasional akan diaktifkan bertahap: payment verification, booking, driver, vehicle, dan withdrawal.</p></article>
            </div>
        </section>
    </main>
</div>
</body>
</html>
