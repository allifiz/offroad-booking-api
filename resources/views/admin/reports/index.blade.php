@extends('layouts.admin')

@section('title', 'Reports')

@section('content')
<div class="mx-auto max-w-6xl">
    <header class="rounded-2xl bg-slate-950 p-6 text-white">
        <p class="text-xs font-bold uppercase tracking-[.2em] text-amber-400">Reporting center</p>
        <h1 class="mt-2 text-3xl font-black">Export laporan CSV</h1>
        <p class="mt-2 text-slate-400">Unduh laporan operasional langsung menggunakan session admin.</p>
    </header>

    <form method="GET" class="mt-6 grid gap-3 rounded-2xl border bg-white p-5 shadow-sm sm:grid-cols-[1fr_1fr_auto]">
        <label class="text-sm font-semibold">Dari
            <input type="date" name="date_from" value="{{ $from->toDateString() }}" class="mt-1 block w-full rounded-lg border-slate-300">
        </label>
        <label class="text-sm font-semibold">Sampai
            <input type="date" name="date_to" value="{{ $to->toDateString() }}" class="mt-1 block w-full rounded-lg border-slate-300">
        </label>
        <button class="self-end rounded-lg bg-slate-950 px-5 py-2.5 font-bold text-white">Terapkan</button>
    </form>

    <section class="mt-6 grid gap-4 md:grid-cols-2">
        @foreach([
            ['title' => 'Bookings', 'description' => 'Booking, customer, paket, peserta, nilai, dan status.', 'route' => 'admin.reports.bookings'],
            ['title' => 'Payments', 'description' => 'Pembayaran, reviewer, waktu proses, dan alasan penolakan.', 'route' => 'admin.reports.payments'],
            ['title' => 'Drivers', 'description' => 'Profil, status operasional, verifikasi, dan saldo poin.', 'route' => 'admin.reports.drivers'],
            ['title' => 'Withdrawals', 'description' => 'Rekening, poin, nominal, processor, dan status.', 'route' => 'admin.reports.withdrawals'],
        ] as $report)
            <article class="rounded-2xl border bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black">{{ $report['title'] }}</h2>
                <p class="mt-2 text-sm text-slate-500">{{ $report['description'] }}</p>
                <a href="{{ route($report['route'], ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString()]) }}" class="mt-5 inline-block rounded-lg bg-amber-500 px-4 py-2.5 font-bold text-slate-950">Download CSV</a>
            </article>
        @endforeach
    </section>
</div>
@endsection