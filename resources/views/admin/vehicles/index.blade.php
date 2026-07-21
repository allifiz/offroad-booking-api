@extends('layouts.admin')

@section('title', 'Kendaraan')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.2em] text-amber-600">Operasional armada</p>
            <h1 class="mt-2 text-3xl font-black">Kendaraan</h1>
            <p class="mt-2 text-slate-600">Kelola kendaraan perusahaan dan kendaraan milik driver.</p>
        </div>
        <a href="{{ route('admin.vehicles.create') }}" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white">Tambah kendaraan</a>
    </div>

    <form method="GET" class="mt-6 grid gap-3 rounded-2xl border bg-white p-4 shadow-sm lg:grid-cols-[1fr_210px_210px_auto]">
        <input name="search" value="{{ request('search') }}" placeholder="Nama, plat, brand, model, driver" class="rounded-xl border-slate-300">
        <select name="status" class="rounded-xl border-slate-300">
            <option value="">Semua status</option>
            @foreach (\App\Enums\VehicleStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst(str_replace('_', ' ', $status->value)) }}</option>
            @endforeach
        </select>
        <select name="ownership_type" class="rounded-xl border-slate-300">
            <option value="">Semua kepemilikan</option>
            @foreach (\App\Enums\VehicleOwnershipType::cases() as $type)
                <option value="{{ $type->value }}" @selected(request('ownership_type') === $type->value)>{{ ucfirst($type->value) }}</option>
            @endforeach
        </select>
        <button class="rounded-xl bg-amber-500 px-5 py-3 font-bold text-slate-950">Filter</button>
    </form>

    <div class="mt-6 overflow-hidden rounded-2xl border bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-4">Kendaraan</th>
                    <th class="px-5 py-4">Pemilik</th>
                    <th class="px-5 py-4">Kapasitas</th>
                    <th class="px-5 py-4">Assignment</th>
                    <th class="px-5 py-4">Status</th>
                    <th class="px-5 py-4"></th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($vehicles as $vehicle)
                    <tr>
                        <td class="px-5 py-4">
                            <p class="font-bold">{{ $vehicle->name }}</p>
                            <p class="text-xs text-slate-500">{{ $vehicle->plate_number }} · {{ trim(($vehicle->brand ?? '').' '.($vehicle->model ?? '')) ?: 'Brand/model belum diisi' }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-semibold">{{ ucfirst($vehicle->ownership_type->value) }}</p>
                            <p class="text-xs text-slate-500">{{ $vehicle->driverProfile?->user?->name ?: 'Perusahaan' }}</p>
                        </td>
                        <td class="px-5 py-4">{{ $vehicle->capacity }} orang</td>
                        <td class="px-5 py-4">{{ $vehicle->driver_assignments_count }}</td>
                        <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">{{ $vehicle->status->value }}</span></td>
                        <td class="px-5 py-4 text-right"><a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="font-bold text-amber-700">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Belum ada kendaraan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t px-5 py-4">{{ $vehicles->links() }}</div>
    </div>
@endsection