<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tourPackage->exists ? 'Edit' : 'Tambah' }} Paket Wisata · Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<main class="mx-auto max-w-4xl p-5 sm:p-8">
    <a href="{{ route('admin.tour-packages.index') }}" class="text-sm font-bold text-amber-700">← Kembali ke paket wisata</a>
    <header class="mt-5 rounded-2xl bg-slate-950 p-6 text-white">
        <p class="text-xs font-bold uppercase tracking-[.2em] text-amber-400">Master data</p>
        <h1 class="mt-2 text-3xl font-black">{{ $tourPackage->exists ? 'Edit paket wisata' : 'Tambah paket wisata' }}</h1>
    </header>

    @if(session('success'))<div class="mt-5 rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="mt-5 rounded-xl bg-red-50 p-4 text-red-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ $tourPackage->exists ? route('admin.tour-packages.update', $tourPackage) : route('admin.tour-packages.store') }}" class="mt-6 space-y-6 rounded-2xl border bg-white p-6 shadow-sm">
        @csrf
        @if($tourPackage->exists) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <label class="sm:col-span-2 text-sm font-bold">Nama
                <input name="name" value="{{ old('name', $tourPackage->name) }}" required class="mt-2 w-full rounded-xl border-slate-300">
            </label>
            <label class="text-sm font-bold">Slug
                <input name="slug" value="{{ old('slug', $tourPackage->slug) }}" placeholder="Kosongkan untuk dibuat otomatis" class="mt-2 w-full rounded-xl border-slate-300">
            </label>
            <label class="text-sm font-bold">Status
                <select name="status" required class="mt-2 w-full rounded-xl border-slate-300">
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $tourPackage->status?->value ?? 'draft') === $status->value)>{{ ucfirst($status->value) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="sm:col-span-2 text-sm font-bold">Deskripsi
                <textarea name="description" rows="5" class="mt-2 w-full rounded-xl border-slate-300">{{ old('description', $tourPackage->description) }}</textarea>
            </label>
            <label class="sm:col-span-2 text-sm font-bold">Meeting point
                <input name="meeting_point" value="{{ old('meeting_point', $tourPackage->meeting_point) }}" class="mt-2 w-full rounded-xl border-slate-300">
            </label>
            <label class="text-sm font-bold">Durasi (menit)
                <input type="number" min="1" name="duration_minutes" value="{{ old('duration_minutes', $tourPackage->duration_minutes) }}" class="mt-2 w-full rounded-xl border-slate-300">
            </label>
            <label class="text-sm font-bold">Harga per orang
                <input type="number" min="0" step="1" name="price_per_person" value="{{ old('price_per_person', $tourPackage->price_per_person) }}" required class="mt-2 w-full rounded-xl border-slate-300">
            </label>
            <label class="text-sm font-bold">Minimum peserta
                <input type="number" min="1" name="minimum_participants" value="{{ old('minimum_participants', $tourPackage->minimum_participants ?: 1) }}" required class="mt-2 w-full rounded-xl border-slate-300">
            </label>
            <label class="text-sm font-bold">Maksimum peserta
                <input type="number" min="1" name="maximum_participants" value="{{ old('maximum_participants', $tourPackage->maximum_participants) }}" class="mt-2 w-full rounded-xl border-slate-300">
            </label>
        </div>

        <div class="flex flex-col gap-3 border-t pt-5 sm:flex-row sm:items-center sm:justify-between">
            <button class="rounded-xl bg-slate-950 px-6 py-3 font-bold text-white">Simpan paket</button>
        </div>
    </form>

    @if($tourPackage->exists)
        <form method="POST" action="{{ route('admin.tour-packages.destroy', $tourPackage) }}" class="mt-4" onsubmit="return confirm('Hapus paket wisata ini?')">
            @csrf @method('DELETE')
            <button class="rounded-xl bg-red-50 px-5 py-3 text-sm font-bold text-red-700">Hapus paket</button>
        </form>
    @endif
</main>
</body>
</html>
