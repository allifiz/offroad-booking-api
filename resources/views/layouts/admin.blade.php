<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') · Offroad Booking</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<div class="min-h-screen lg:grid lg:grid-cols-[270px_1fr]">
    <aside class="border-b border-slate-800 bg-slate-950 text-white lg:min-h-screen lg:border-b-0 lg:border-r">
        <div class="flex items-center justify-between px-5 py-5 lg:block lg:px-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-amber-400">Offroad Booking</p>
                <h1 class="mt-2 text-xl font-black">Admin Panel</h1>
            </div>
            <details class="relative lg:hidden">
                <summary class="cursor-pointer rounded-lg border border-white/20 px-3 py-2 text-sm font-bold">Menu</summary>
                <nav class="absolute right-0 z-20 mt-2 w-64 space-y-1 rounded-xl bg-slate-950 p-3 shadow-2xl">
                    @include('layouts.partials.admin-navigation')
                </nav>
            </details>
        </div>
        <nav class="hidden space-y-1 px-4 pb-6 text-sm lg:block">
            @include('layouts.partials.admin-navigation')
        </nav>
        <div class="hidden border-t border-white/10 px-6 py-5 lg:block">
            <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
            <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
            <form method="POST" action="{{ route('admin.logout') }}" class="mt-4">
                @csrf
                <button class="text-sm font-medium text-slate-400 hover:text-white">Keluar</button>
            </form>
        </div>
    </aside>

    <main class="min-w-0 p-5 sm:p-8 lg:p-10">
        @if(session('success'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>
</div>
</body>
</html>
