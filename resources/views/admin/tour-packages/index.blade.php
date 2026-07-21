@extends('layouts.admin')

@section('title', 'Paket Wisata')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.2em] text-amber-600">Master data</p>
            <h1 class="mt-2 text-3xl font-black">Paket Wisata</h1>
            <p class="mt-2 text-slate-600">Kelola paket yang tampil dan dapat dipesan customer.</p>
        </div>
        <a href="{{ route('admin.tour-packages.create') }}" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white">Tambah paket</a>
    </div>

    <form method="GET" class="mt-6 grid gap-3 rounded-2xl border bg-white p-4 shadow-sm sm:grid-cols-[1fr_220px_auto]">
        <input name="search" value="{{ request('search') }}" placeholder="Nama, slug, meeting point" class="rounded-xl border-slate-300">
        <select name="status" class="rounded-xl border-slate-300">
            <option value="">Semua status</option>
            @foreach(\App\Enums\TourPackageStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst($status->value) }}</option>
            @endforeach
        </select>
        <button class="rounded-xl bg-amber-500 px-5 py-3 font-bold text-slate-950">Filter</button>
    </form>

    <div class="mt-6 overflow-hidden rounded-2xl border bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-5 py-4">Paket</th><th class="px-5 py-4">Harga</th><th class="px-5 py-4">Peserta</th><th class="px-5 py-4">Booking</th><th class="px-5 py-4">Status</th><th class="px-5 py-4"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($tourPackages as $tourPackage)
                    <tr>
                        <td class="px-5 py-4"><p class="font-bold">{{ $tourPackage->name }}</p><p class="text-xs text-slate-500">{{ $tourPackage->slug }} · {{ $tourPackage->meeting_point ?: 'Meeting point belum diisi' }}</p></td>
                        <td class="px-5 py-4 font-semibold">Rp{{ number_format((float) $tourPackage->price_per_person, 0, ',', '.') }}</td>
                        <td class="px-5 py-4">{{ $tourPackage->minimum_participants }}–{{ $tourPackage->maximum_participants ?: '∞' }}</td>
                        <td class="px-5 py-4">{{ $tourPackage->bookings_count }}</td>
                        <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">{{ $tourPackage->status->value }}</span></td>
                        <td class="px-5 py-4 text-right"><a href="{{ route('admin.tour-packages.edit', $tourPackage) }}" class="font-bold text-amber-700">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Belum ada paket wisata.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t px-5 py-4">{{ $tourPackages->links() }}</div>
    </div>
@endsection