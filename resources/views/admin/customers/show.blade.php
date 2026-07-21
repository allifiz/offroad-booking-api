<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $customer->name }} · Customer Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<main class="mx-auto max-w-7xl p-5 sm:p-8">
    <a href="{{ route('admin.customers.index') }}" class="text-sm font-bold text-amber-700">← Kembali ke customers</a>

    @if(session('success'))<div class="mt-5 rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mt-5 rounded-xl bg-red-50 p-4 text-red-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <header class="mt-5 grid gap-6 rounded-2xl bg-slate-950 p-6 text-white lg:grid-cols-[1fr_340px]">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.2em] text-amber-400">Customer detail</p>
            <h1 class="mt-2 text-3xl font-black">{{ $customer->name }}</h1>
            <p class="mt-2 text-slate-400">{{ $customer->email }} · {{ $customer->phone ?: 'Tanpa nomor telepon' }}</p>
            <p class="mt-3 text-sm text-slate-400">Terdaftar {{ $customer->created_at->format('d M Y H:i') }}</p>
        </div>
        <form method="POST" action="{{ route('admin.customers.status', $customer) }}" class="space-y-3 rounded-xl bg-white/10 p-4">
            @csrf @method('PATCH')
            <label class="text-xs font-bold uppercase tracking-wide text-slate-300">Status akun
                <select name="status" class="mt-2 w-full rounded-xl border-white/20 bg-slate-900 text-white">
                    @foreach(\App\Enums\UserStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected($customer->status === $status)>{{ ucfirst($status->value) }}</option>
                    @endforeach
                </select>
            </label>
            <button class="w-full rounded-xl bg-amber-500 px-5 py-3 font-bold text-slate-950">Simpan status</button>
            <p class="text-xs leading-5 text-slate-400">Perubahan status mencabut semua token API aktif customer.</p>
        </form>
    </header>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1fr_380px]">
        <div class="overflow-hidden rounded-2xl border bg-white shadow-sm">
            <div class="border-b px-5 py-4"><h2 class="font-bold">Riwayat booking</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Booking</th><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Nilai</th><th class="px-5 py-3">Status</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($bookings as $booking)
                        <tr>
                            <td class="px-5 py-4"><a href="{{ route('admin.bookings.show', $booking) }}" class="font-bold text-amber-700">{{ $booking->booking_code }}</a><p class="text-xs text-slate-500">{{ $booking->tourPackage?->name }}</p></td>
                            <td class="px-5 py-4">{{ $booking->tour_date->format('d M Y') }}</td>
                            <td class="px-5 py-4 font-semibold">Rp{{ number_format((float) $booking->total_amount, 0, ',', '.') }}</td>
                            <td class="px-5 py-4">{{ $booking->status->value }} · {{ $booking->payment_status->value }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">Belum ada booking.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t px-5 py-4">{{ $bookings->links() }}</div>
        </div>

        <aside class="overflow-hidden rounded-2xl border bg-white shadow-sm">
            <div class="border-b px-5 py-4"><h2 class="font-bold">Pembayaran terbaru</h2></div>
            <div class="divide-y divide-slate-100">
            @forelse($payments as $payment)
                <a href="{{ route('admin.payments.show', $payment) }}" class="block p-5 hover:bg-slate-50">
                    <div class="flex items-center justify-between gap-3"><strong>{{ $payment->booking?->booking_code }}</strong><span class="text-xs font-bold">{{ $payment->status->value }}</span></div>
                    <p class="mt-2 text-sm text-slate-500">Rp{{ number_format((float) $payment->amount, 0, ',', '.') }} · {{ $payment->method }}</p>
                </a>
            @empty
                <p class="p-5 text-sm text-slate-500">Belum ada pembayaran.</p>
            @endforelse
            </div>
        </aside>
    </section>
</main>
</body>
</html>
