@extends('layouts.admin')

@section('title', 'Buat Travel Group')

@section('content')
    <a href="{{ route('admin.travel-groups.index') }}" class="text-sm font-bold text-amber-700">← Travel groups</a>

    <header class="mt-5 rounded-2xl bg-slate-950 p-6 text-white">
        <p class="text-xs font-bold uppercase tracking-[.2em] text-amber-400">Operations</p>
        <h1 class="mt-2 text-3xl font-black">Buat travel group</h1>
    </header>

    <form method="POST" action="{{ route('admin.travel-groups.store') }}" class="mt-6 space-y-6 rounded-2xl border bg-white p-6 shadow-sm">
        @csrf
        <div class="grid gap-5 sm:grid-cols-2">
            <label class="sm:col-span-2 text-sm font-bold">Nama
                <input name="name" value="{{ old('name') }}" required class="mt-2 w-full rounded-xl border-slate-300">
            </label>
            <label class="text-sm font-bold">Sumber
                <select name="source" required class="mt-2 w-full rounded-xl border-slate-300">
                    @foreach ($sources as $source)
                        <option value="{{ $source->value }}" @selected(old('source') === $source->value)>{{ ucfirst($source->value) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-bold">Batas anggota
                <input type="number" min="1" max="100" name="member_limit" value="{{ old('member_limit') }}" class="mt-2 w-full rounded-xl border-slate-300">
            </label>
            <label class="sm:col-span-2 text-sm font-bold">Leader
                <select name="leader_user_id" class="mt-2 w-full rounded-xl border-slate-300">
                    <option value="">Belum ditentukan</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) old('leader_user_id') === (string) $user->id)>{{ $user->name }} · {{ $user->email }}</option>
                    @endforeach
                </select>
            </label>
            <label class="sm:col-span-2 text-sm font-bold">Anggota
                <select name="member_user_ids[]" multiple size="8" class="mt-2 w-full rounded-xl border-slate-300">
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(in_array($user->id, old('member_user_ids', [])))>{{ $user->name }} · {{ $user->role->value }}</option>
                    @endforeach
                </select>
                <span class="mt-1 block text-xs font-normal text-slate-500">Gunakan Ctrl/Cmd untuk memilih beberapa anggota.</span>
            </label>
            <label class="sm:col-span-2 text-sm font-bold">Catatan
                <textarea name="notes" rows="5" class="mt-2 w-full rounded-xl border-slate-300">{{ old('notes') }}</textarea>
            </label>
        </div>
        <button class="rounded-xl bg-slate-950 px-6 py-3 font-bold text-white">Simpan travel group</button>
    </form>
@endsection