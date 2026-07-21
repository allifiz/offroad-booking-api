<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(UserStatus::class)],
        ]);

        $customers = User::query()
            ->where('role', UserRole::CUSTOMER)
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->withCount([
                'bookingsAsCustomer as bookings_count',
                'paymentsAsCustomer as payments_count',
            ])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $customer): View
    {
        abort_unless($customer->role === UserRole::CUSTOMER, 404);

        $bookings = Booking::query()
            ->with('tourPackage')
            ->where('customer_id', $customer->id)
            ->latest()
            ->paginate(10, ['*'], 'bookings_page');

        $payments = Payment::query()
            ->with('booking')
            ->where('customer_id', $customer->id)
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.customers.show', compact('customer', 'bookings', 'payments'));
    }

    public function updateStatus(Request $request, User $customer): RedirectResponse
    {
        abort_unless($customer->role === UserRole::CUSTOMER, 404);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(UserStatus::class)],
        ]);

        $customer->update(['status' => $validated['status']]);
        $customer->tokens()->delete();

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Status customer berhasil diperbarui. Sesi API aktif telah dicabut.');
    }
}
