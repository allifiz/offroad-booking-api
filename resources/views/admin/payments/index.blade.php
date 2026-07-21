@extends('layouts.admin')

@section('title', 'Pembayaran')

@section('content')
<div>
    <p class="text-xs font-bold uppercase tracking-[.2em] text-amber-600">Finance operations</p>
    <h1 class="mt-2 text-3xl font-black">Verifikasi pembayaran</h1>
    <p class="mt-2 text-slate-600">Tinjau bukti pembayaran dan perbarui status transaksi customer.</p>
</div>

<section class="mt-7 grid gap-3 sm:grid-cols-3">
    @foreach (['pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed'] as $key => $label)
        <a href="{{ route('admin.payments.index', ['status' => $key]) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">{{ $label }}</p>
            <p class="mt-2 text-3xl font-black">{{ number_format($counts[$key] ?? 0) }}</p>
        </a>
    @endforeach
</section>

<form method="GET" class="mt-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_180px_auto]">
    <input name="search" value="{{ request('search') }}" placeholder="Booking, nama, atau email" class="rounded-xl border-slate-300">
    <select name="status" class="rounded-xl border-slate-300">
        <option value="">Semua status</option>
        @foreach (['unpaid', 'pending', 'paid', 'refunded', 'failed'] as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <button class="rounded-xl bg-slate-950 px-5 py-3 font-bold text-white">Filter</button>
</form>

<div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr><th class="px-5 py-3">Booking</th><th class="px-5 py-3">Customer</th><th class="px-5 py-3">Jumlah</th><th class="px-5 py-3">Metode</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($payments as $payment)
                    <tr>
                        <td class="px-5 py-4"><p class="font-bold">{{ $payment->booking->booking_code }}</p><p class="text-xs text-slate-500">{{ $payment->booking->tourPackage?->name }}</p></td>
                        <td class="px-5 py-4"><p>{{ $payment->customer->name }}</p><p class="text-xs text-slate-500">{{ $payment->customer->email }}</p></td>
                        <td class="px-5 py-4 font-semibold">Rp{{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                        <td class="px-5 py-4">{{ $payment->method }}</td>
                        <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold">{{ $payment->status->value }}</span></td>
                        <td class="px-5 py-4 text-right"><a class="font-bold text-amber-700" href="{{ route('admin.payments.show', $payment) }}">Tinjau</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">Tidak ada pembayaran.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-slate-100 px-5 py-4">{{ $payments->links() }}</div>
</div>
@endsection
