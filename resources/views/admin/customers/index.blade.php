<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customers · Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<main class="mx-auto max-w-7xl p-5 sm:p-8">
    <div>
        <a href="{{ route('admin.dashboard') }}" class="text-sm font-bold text-amber-700">← Dashboard</a>
        <p class="mt-4 text-xs font-bold uppercase tracking-[.2em] text-amber-600">User management</p>
        <h1 class="mt-2 text-3xl font-black">Customers</h1>
        <p class="mt-2 text-slate-600">Lihat riwayat transaksi dan kelola status akun customer.</p>
    </div>

    <form method="GET" class="mt-6 grid gap-3 rounded-2xl border bg-white p-4 shadow-sm sm:grid-cols-[1fr_220px_auto]">
        <input name="search" value="{{ request('search') }}" placeholder="Nama, email, atau telepon" class="rounded-xl border-slate-300">
        <select name="status" class="rounded-xl border-slate-300">
            <option value="">Semua status</option>
            @foreach(\App\Enums\UserStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst($status->value) }}</option>
            @endforeach
        </select>
        <button class="rounded-xl bg-amber-500 px-5 py-3 font-bold text-slate-950">Filter</button>
    </form>

    <div class="mt-6 overflow-hidden rounded-2xl border bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr><th class="px-5 py-4">Customer</th><th class="px-5 py-4">Kontak</th><th class="px-5 py-4">Booking</th><th class="px-5 py-4">Payment</th><th class="px-5 py-4">Status</th><th class="px-5 py-4"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($customers as $customer)
                    <tr>
                        <td class="px-5 py-4"><p class="font-bold">{{ $customer->name }}</p><p class="text-xs text-slate-500">Bergabung {{ $customer->created_at->format('d M Y') }}</p></td>
                        <td class="px-5 py-4"><p>{{ $customer->email }}</p><p class="text-xs text-slate-500">{{ $customer->phone ?: 'Tanpa nomor telepon' }}</p></td>
                        <td class="px-5 py-4 font-semibold">{{ $customer->bookings_count }}</td>
                        <td class="px-5 py-4 font-semibold">{{ $customer->payments_count }}</td>
                        <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">{{ $customer->status->value }}</span></td>
                        <td class="px-5 py-4 text-right"><a href="{{ route('admin.customers.show', $customer) }}" class="font-bold text-amber-700">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Belum ada customer.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t px-5 py-4">{{ $customers->links() }}</div>
    </div>
</main>
</body>
</html>
