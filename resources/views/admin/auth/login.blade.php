<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin · Offroad Booking</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
<div class="grid min-h-screen lg:grid-cols-2">
    <section class="hidden border-r border-white/10 bg-[radial-gradient(circle_at_top_left,_rgba(245,158,11,0.24),_transparent_40%),linear-gradient(145deg,#0f172a,#020617)] p-12 lg:flex lg:flex-col lg:justify-between">
        <div class="text-sm font-semibold uppercase tracking-[0.28em] text-amber-400">Offroad Booking</div>
        <div class="max-w-xl">
            <p class="mb-4 text-sm uppercase tracking-[0.24em] text-slate-400">Operations command center</p>
            <h1 class="text-5xl font-black leading-tight">Kelola perjalanan, armada, dan pembayaran dari satu panel.</h1>
            <p class="mt-6 max-w-lg text-lg leading-8 text-slate-400">Dashboard operasional untuk verifikasi, assignment driver, laporan, withdrawal, dan audit aktivitas.</p>
        </div>
        <p class="text-sm text-slate-500">Akses terbatas untuk administrator aktif.</p>
    </section>

    <main class="flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-md">
            <div class="mb-10 lg:hidden">
                <div class="text-sm font-semibold uppercase tracking-[0.28em] text-amber-400">Offroad Booking</div>
                <h1 class="mt-4 text-3xl font-black">Admin panel</h1>
            </div>

            <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-8 shadow-2xl shadow-black/20 backdrop-blur">
                <div class="mb-8">
                    <p class="text-sm font-semibold text-amber-400">Selamat datang kembali</p>
                    <h2 class="mt-2 text-3xl font-bold">Masuk sebagai admin</h2>
                    <p class="mt-2 text-sm text-slate-400">Gunakan akun administrator aktif.</p>
                </div>

                <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="mb-2 block text-sm font-medium text-slate-300">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white outline-none ring-amber-400 transition focus:ring-2">
                        @error('email')<p class="mt-2 text-sm text-red-400">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password" class="mb-2 block text-sm font-medium text-slate-300">Password</label>
                        <input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white outline-none ring-amber-400 transition focus:ring-2">
                    </div>
                    <label class="flex items-center gap-3 text-sm text-slate-400">
                        <input type="checkbox" name="remember" value="1" class="size-4 rounded border-white/20 bg-slate-900 text-amber-500 focus:ring-amber-400">
                        Ingat sesi saya
                    </label>
                    <button type="submit" class="w-full rounded-xl bg-amber-500 px-4 py-3 font-bold text-slate-950 transition hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:ring-offset-2 focus:ring-offset-slate-950">Masuk ke dashboard</button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
