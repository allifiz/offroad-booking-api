<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $vehicle->exists ? 'Edit' : 'Tambah' }} Kendaraan · Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<main class="mx-auto max-w-4xl p-5 sm:p-8">
    <a href="{{ route('admin.vehicles.index') }}" class="text-sm font-bold text-amber-700">← Kembali ke kendaraan</a>
    <header class="mt-5 rounded-2xl bg-slate-950 p-6 text-white">
        <p class="text-xs font-bold uppercase tracking-[.2em] text-amber-400">Operasional armada</p>
        <h1 class="mt-2 text-3xl font-black">{{ $vehicle->exists ? 'Edit kendaraan' : 'Tambah kendaraan' }}</h1>
    </header>

    @if (session('success'))
        <div class="mt-5 rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mt-5 rounded-xl bg-red-50 p-4 text-red-800">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $vehicle->exists ? route('admin.vehicles.update', $vehicle) : route('admin.vehicles.store') }}" class="mt-6 space-y-6 rounded-2xl border bg-white p-6 shadow-sm">
        @csrf
        @if ($vehicle->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <label class="sm:col-span-2 text-sm font-bold">Nama kendaraan
                <input name="name" value="{{ old('name', $vehicle->name) }}" required class="mt-2 w-full rounded-xl border-slate-300">
            </label>
            <label class="text-sm font-bold">Nomor polisi
                <input name="plate_number" value="{{ old('plate_number', $vehicle->plate_number) }}" required class="mt-2 w-full rounded-xl border-slate-300">
            </label>
            <label class="text-sm font-bold">Status
                <select name="status" required class="mt-2 w-full rounded-xl border-slate-300">
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $vehicle->status?->value ?? 'available') === $status->value)>{{ ucfirst(str_replace('_', ' ', $status->value)) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-bold">Kepemilikan
                <select name="ownership_type" required class="mt-2 w-full rounded-xl border-slate-300">
                    @foreach ($ownershipTypes as $type)
                        <option value="{{ $type->value }}" @selected(old('ownership_type', $vehicle->ownership_type?->value ?? 'company') === $type->value)>{{ ucfirst($type->value) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-bold">Driver pemilik
                <select name="driver_profile_id" class="mt-2 w-full rounded-xl border-slate-300">
                    <option value="">Tidak ada / milik perusahaan</option>
                    @foreach ($drivers as $driver)
                        <option value="{{ $driver->id }}" @selected((string) old('driver_profile_id', $vehicle->driver_profile_id) === (string) $driver->id)>{{ $driver->user?->name }} · {{ $driver->license_number ?: 'SIM belum diisi' }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-bold">Brand
                <input name="brand" value="{{ old('brand', $vehicle->brand) }}" class="mt-2 w-full rounded-xl border-slate-300">
            </label>
            <label class="text-sm font-bold">Model
                <input name="model" value="{{ old('model', $vehicle->model) }}" class="mt-2 w-full rounded-xl border-slate-300">
            </label>
            <label class="text-sm font-bold">Tahun
                <input type="number" min="1900" max="{{ now()->year + 1 }}" name="year" value="{{ old('year', $vehicle->year) }}" class="mt-2 w-full rounded-xl border-slate-300">
            </label>
            <label class="text-sm font-bold">Kapasitas
                <input type="number" min="1" max="100" name="capacity" value="{{ old('capacity', $vehicle->capacity ?: 1) }}" required class="mt-2 w-full rounded-xl border-slate-300">
            </label>
            <label class="sm:col-span-2 text-sm font-bold">Catatan
                <textarea name="notes" rows="5" class="mt-2 w-full rounded-xl border-slate-300">{{ old('notes', $vehicle->notes) }}</textarea>
            </label>
        </div>

        <button class="rounded-xl bg-slate-950 px-6 py-3 font-bold text-white">Simpan kendaraan</button>
    </form>

    @if ($vehicle->exists)
        <form method="POST" action="{{ route('admin.vehicles.destroy', $vehicle) }}" class="mt-4" onsubmit="return confirm('Hapus kendaraan ini?')">
            @csrf
            @method('DELETE')
            <button class="rounded-xl bg-red-50 px-5 py-3 text-sm font-bold text-red-700">Hapus kendaraan</button>
        </form>
    @endif
</main>
</body>
</html>
