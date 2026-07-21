@extends('layouts.admin')

@section('title', 'Detail Pembayaran')

@section('content')
<a href="{{ route('admin.payments.index') }}" class="text-sm font-bold text-amber-700">← Kembali ke pembayaran</a>

<header class="mt-5">
    <p class="text-sm font-semibold text-amber-600">Payment review</p>
    <h1 class="mt-1 text-3xl font-black">{{ $payment->booking->booking_code }}</h1>
    <p class="mt-2 text-slate-500">Dikirim {{ optional($payment->submitted_at)->format('d M Y H:i') ?? '-' }}</p>
</header>

<div class="mt-7 grid gap-6 lg:grid-cols-[1fr_360px]">
    <section class="space-y-6">
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-bold">Informasi transaksi</h2>
            <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                <div><dt class="text-xs uppercase text-slate-500">Customer</dt><dd class="mt-1 font-semibold">{{ $payment->customer->name }}</dd><dd class="text-sm text-slate-500">{{ $payment->customer->email }}</dd></div>
                <div><dt class="text-xs uppercase text-slate-500">Paket wisata</dt><dd class="mt-1 font-semibold">{{ $payment->booking->tourPackage?->name }}</dd></div>
                <div><dt class="text-xs uppercase text-slate-500">Jumlah</dt><dd class="mt-1 text-2xl font-black">Rp{{ number_format((float) $payment->amount, 0, ',', '.') }}</dd></div>
                <div><dt class="text-xs uppercase text-slate-500">Metode</dt><dd class="mt-1 font-semibold">{{ $payment->method }}</dd></div>
                <div><dt class="text-xs uppercase text-slate-500">Status</dt><dd class="mt-1 font-semibold">{{ $payment->status->value }}</dd></div>
                <div><dt class="text-xs uppercase text-slate-500">Reviewer</dt><dd class="mt-1 font-semibold">{{ $payment->reviewer?->name ?? '-' }}</dd></div>
            </dl>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-bold">Bukti pembayaran</h2>
            @if ($payment->proof_path)
                <a href="{{ asset('storage/'.$payment->proof_path) }}" target="_blank" rel="noopener" class="mt-4 inline-flex rounded-xl bg-slate-950 px-5 py-3 font-bold text-white">Buka bukti pembayaran</a>
                <p class="mt-3 break-all text-xs text-slate-500">{{ $payment->proof_path }}</p>
            @else
                <p class="mt-4 text-slate-500">Bukti pembayaran tidak tersedia.</p>
            @endif
        </article>
    </section>

    <aside>
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-bold">Keputusan verifikasi</h2>
            @if ($payment->status->value === 'pending')
                <form method="POST" action="{{ route('admin.payments.update', $payment) }}" class="mt-5 space-y-4">
                    @csrf @method('PATCH')
                    <label class="block text-sm font-semibold">Status
                        <select name="status" class="mt-2 block w-full rounded-xl border-slate-300">
                            <option value="paid">Setujui pembayaran</option>
                            <option value="failed" @selected(old('status') === 'failed')>Tolak pembayaran</option>
                        </select>
                    </label>
                    <label class="block text-sm font-semibold">Alasan penolakan
                        <textarea name="rejection_reason" rows="5" class="mt-2 block w-full rounded-xl border-slate-300" placeholder="Wajib bila pembayaran ditolak">{{ old('rejection_reason') }}</textarea>
                    </label>
                    <button class="w-full rounded-xl bg-amber-500 px-5 py-3 font-black text-slate-950">Simpan keputusan</button>
                </form>
            @else
                <div class="mt-5 rounded-xl bg-slate-100 p-4">
                    <p class="font-bold">Pembayaran sudah diproses.</p>
                    <p class="mt-2 text-sm text-slate-600">Status: {{ $payment->status->value }}</p>
                    @if ($payment->rejection_reason)<p class="mt-2 text-sm text-red-700">{{ $payment->rejection_reason }}</p>@endif
                </div>
            @endif
        </article>
    </aside>
</div>
@endsection
