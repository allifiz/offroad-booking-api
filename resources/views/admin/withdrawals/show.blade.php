@extends('layouts.admin')

@section('title', 'Withdrawal #'.$withdrawal->id)

@section('content')
<a href="{{ route('admin.withdrawals.index') }}" class="text-sm font-bold text-amber-700">← Kembali ke withdrawals</a>

<header class="mt-5 rounded-2xl bg-slate-950 p-6 text-white">
    <p class="text-xs font-bold uppercase tracking-[.2em] text-amber-400">Withdrawal detail</p>
    <h1 class="mt-2 text-3xl font-black">#{{ $withdrawal->id }}</h1>
    <p class="mt-2 text-slate-400">{{ $withdrawal->driverProfile?->user?->name }} · {{ $withdrawal->status->value }}</p>
</header>

<section class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
    <div class="space-y-6">
        <article class="rounded-2xl border bg-white p-6 shadow-sm"><h2 class="font-bold">Informasi payout</h2><dl class="mt-4 grid gap-4 sm:grid-cols-2"><div><dt class="text-xs uppercase text-slate-500">Poin</dt><dd class="font-semibold">{{ number_format($withdrawal->points) }}</dd></div><div><dt class="text-xs uppercase text-slate-500">Jumlah</dt><dd class="font-semibold">Rp{{ number_format((float) $withdrawal->amount, 0, ',', '.') }}</dd></div><div><dt class="text-xs uppercase text-slate-500">Bank</dt><dd class="font-semibold">{{ $withdrawal->bank_name }}</dd></div><div><dt class="text-xs uppercase text-slate-500">Nomor rekening</dt><dd class="font-semibold">{{ $withdrawal->account_number }}</dd></div><div><dt class="text-xs uppercase text-slate-500">Nama rekening</dt><dd class="font-semibold">{{ $withdrawal->account_name }}</dd></div><div><dt class="text-xs uppercase text-slate-500">Diminta</dt><dd class="font-semibold">{{ optional($withdrawal->requested_at)->format('d M Y H:i') }}</dd></div></dl></article>
        <article class="rounded-2xl border bg-white p-6 shadow-sm"><h2 class="font-bold">Saldo driver</h2><p class="mt-3 text-sm text-slate-500">Available: {{ number_format($withdrawal->driverProfile?->available_points ?? 0) }} · Held: {{ number_format($withdrawal->driverProfile?->held_points ?? 0) }}</p>@if($withdrawal->rejection_reason)<p class="mt-4 rounded-xl bg-red-50 p-4 text-sm text-red-800">{{ $withdrawal->rejection_reason }}</p>@endif</article>
    </div>
    <aside class="rounded-2xl border bg-white p-5 shadow-sm"><h2 class="font-bold">Proses withdrawal</h2><form method="POST" action="{{ route('admin.withdrawals.update', $withdrawal) }}" class="mt-4 space-y-3">@csrf @method('PATCH')<select name="status" class="w-full rounded-lg border-slate-300"><option value="approved">approved</option><option value="rejected">rejected</option><option value="paid">paid</option></select><textarea name="rejection_reason" rows="4" placeholder="Alasan penolakan" class="w-full rounded-lg border-slate-300"></textarea><button class="w-full rounded-lg bg-slate-950 px-4 py-2.5 font-bold text-white">Simpan status</button></form><p class="mt-4 text-xs leading-5 text-slate-500">Pending hanya dapat menjadi approved/rejected. Approved hanya dapat menjadi paid.</p></aside>
</section>
@endsection
