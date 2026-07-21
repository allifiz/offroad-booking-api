<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $booking->booking_code }} · Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<main class="mx-auto max-w-6xl p-5 sm:p-8">
    <a href="{{ route('admin.bookings.index') }}" class="text-sm font-bold text-amber-700">← Kembali ke bookings</a>

    @if (session('success'))
        <div class="mt-4 rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="mt-4 rounded-xl bg-red-50 p-4 text-red-800">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <header class="mt-5 flex flex-col gap-4 rounded-2xl bg-slate-950 p-6 text-white sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.2em] text-amber-400">Booking detail</p>
            <h1 class="mt-2 text-3xl font-black">{{ $booking->booking_code }}</h1>
            <p class="mt-2 text-slate-400">{{ $booking->customer?->name }} · {{ $booking->tourPackage?->name }}</p>
        </div>
        <div class="text-right">
            <p class="text-sm text-slate-400">Total</p>
            <p class="text-2xl font-black">Rp{{ number_format((float) $booking->total_amount, 0, ',', '.') }}</p>
        </div>
    </header>

    <section class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
        <div class="space-y-6">
            <article class="rounded-2xl border bg-white p-6 shadow-sm">
                <h2 class="font-bold">Informasi perjalanan</h2>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-xs uppercase text-slate-500">Tanggal</dt><dd class="font-semibold">{{ \Illuminate\Support\Carbon::parse($booking->tour_date)->format('d M Y') }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Peserta</dt><dd class="font-semibold">{{ $booking->participant_count }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Status</dt><dd class="font-semibold">{{ $booking->status->value }}</dd></div>
                    <div><dt class="text-xs uppercase text-slate-500">Pembayaran</dt><dd class="font-semibold">{{ $booking->payment_status->value }}</dd></div>
                </dl>
            </article>

            <article class="rounded-2xl border bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold">Assignments</h2>
                    <span class="text-sm text-slate-500">{{ $booking->driverAssignments->count() }} data</span>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($booking->driverAssignments as $assignment)
                        <div class="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-bold">{{ $assignment->driver?->name }}</p>
                                <p class="text-sm text-slate-500">{{ $assignment->vehicle?->name }} · {{ $assignment->status->value }} · kapasitas {{ $assignment->vehicle?->capacity }}</p>
                            </div>
                            @if ($assignment->status->value !== 'cancelled')
                                <form method="POST" action="{{ route('admin.bookings.assignments.cancel', [$booking, $assignment]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="rounded-lg bg-red-50 px-4 py-2 text-sm font-bold text-red-700">Batalkan</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada assignment.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-2xl border bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-bold">Alokasi peserta</h2>
                        <p class="text-sm text-slate-500">Peserta hanya dapat dialokasikan ke assignment accepted.</p>
                    </div>
                    <span class="text-sm text-slate-500">{{ $booking->participants->count() }} peserta</span>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($booking->participants as $participant)
                        <form method="POST" action="{{ route('admin.bookings.allocations.update', $booking) }}" class="grid gap-3 rounded-xl border p-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="booking_participant_id" value="{{ $participant->id }}">
                            <div>
                                <p class="font-bold">{{ $participant->name }}</p>
                                <p class="text-sm text-slate-500">{{ $participant->phone ?: 'Tanpa nomor telepon' }}</p>
                            </div>
                            <label class="text-xs font-semibold uppercase text-slate-500">
                                Kendaraan
                                <select name="driver_assignment_id" class="mt-1 w-full rounded-lg border-slate-300">
                                    <option value="">Belum dialokasikan</option>
                                    @foreach ($acceptedAssignments as $assignment)
                                        <option value="{{ $assignment->id }}" @selected($participant->vehicleAllocation?->driver_assignment_id === $assignment->id)>
                                            {{ $assignment->driver?->name }} — {{ $assignment->vehicle?->name }} ({{ $assignment->vehicle?->capacity }} kursi)
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <button class="rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-bold text-white">Simpan</button>
                        </form>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada data peserta.</p>
                    @endforelse
                </div>
            </article>
        </div>

        <aside class="space-y-6">
            <article class="rounded-2xl border bg-white p-5 shadow-sm">
                <h2 class="font-bold">Ubah status</h2>
                <form method="POST" action="{{ route('admin.bookings.status', $booking) }}" class="mt-4 space-y-3">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="w-full rounded-lg border-slate-300">
                        @foreach (\App\Enums\BookingStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected($booking->status === $status)>{{ $status->value }}</option>
                        @endforeach
                    </select>
                    <button class="w-full rounded-lg bg-slate-950 px-4 py-2.5 font-bold text-white">Simpan status</button>
                </form>
                <p class="mt-3 text-xs leading-5 text-slate-500">Completion memakai lifecycle service yang sama dengan API dan otomatis memberi reward driver secara idempotent.</p>
            </article>

            <article class="rounded-2xl border bg-white p-5 shadow-sm">
                <h2 class="font-bold">Tawarkan driver</h2>
                <form method="POST" action="{{ route('admin.bookings.assignments.store', $booking) }}" class="mt-4 space-y-3">
                    @csrf
                    <select name="driver_id" class="w-full rounded-lg border-slate-300">
                        <option value="">Pilih driver</option>
                        @foreach ($drivers as $driver)
                            <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                        @endforeach
                    </select>
                    <select name="vehicle_id" class="w-full rounded-lg border-slate-300">
                        <option value="">Pilih kendaraan</option>
                        @foreach ($drivers as $driver)
                            @php($driverVehicles = $driver->driverProfile?->vehicles ?? collect())
                            @foreach ($driverVehicles as $vehicle)
                                <option value="{{ $vehicle->id }}">{{ $driver->name }} — {{ $vehicle->name }} ({{ $vehicle->plate_number }})</option>
                            @endforeach
                        @endforeach
                    </select>
                    <button class="w-full rounded-lg bg-amber-500 px-4 py-2.5 font-bold text-slate-950">Buat penawaran</button>
                </form>
            </article>
        </aside>
    </section>
</main>
</body>
</html>
