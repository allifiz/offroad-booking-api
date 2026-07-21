@php
    $items = [
        ['route' => 'admin.dashboard', 'pattern' => 'admin.dashboard', 'label' => 'Dashboard'],
        ['route' => 'admin.tour-packages.index', 'pattern' => 'admin.tour-packages.*', 'label' => 'Paket Wisata'],
        ['route' => 'admin.travel-groups.index', 'pattern' => 'admin.travel-groups.*', 'label' => 'Travel Groups'],
        ['route' => 'admin.customers.index', 'pattern' => 'admin.customers.*', 'label' => 'Customers'],
        ['route' => 'admin.bookings.index', 'pattern' => 'admin.bookings.*', 'label' => 'Bookings'],
        ['route' => 'admin.payments.index', 'pattern' => 'admin.payments.*', 'label' => 'Payments'],
        ['route' => 'admin.drivers.index', 'pattern' => 'admin.drivers.*', 'label' => 'Drivers'],
        ['route' => 'admin.vehicles.index', 'pattern' => 'admin.vehicles.*', 'label' => 'Vehicles'],
        ['route' => 'admin.withdrawals.index', 'pattern' => 'admin.withdrawals.*', 'label' => 'Withdrawals'],
        ['route' => 'admin.reports.index', 'pattern' => 'admin.reports.*', 'label' => 'Reports'],
        ['route' => 'admin.audit-logs.index', 'pattern' => 'admin.audit-logs.*', 'label' => 'Audit Logs'],
    ];
@endphp

@foreach($items as $item)
    <a href="{{ route($item['route']) }}"
       class="block rounded-xl px-4 py-3 font-semibold transition {{ request()->routeIs($item['pattern']) ? 'bg-amber-500 text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
        {{ $item['label'] }}
    </a>
@endforeach

<form method="POST" action="{{ route('admin.logout') }}" class="pt-2 lg:hidden">
    @csrf
    <button class="w-full rounded-xl border border-white/20 px-4 py-3 text-left font-semibold text-slate-300">Keluar</button>
</form>
