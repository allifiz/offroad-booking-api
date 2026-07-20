<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\TourPackageStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\TourPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminWebPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_show_and_approve_pending_payment(): void
    {
        Notification::fake();
        [$customer, $booking, $payment] = $this->createPendingPayment();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/payments?status=pending')
            ->assertOk()
            ->assertSee($booking->booking_code)
            ->assertSee($customer->email);

        $this->actingAs($admin)
            ->get("/admin/payments/{$payment->id}")
            ->assertOk()
            ->assertSee('Keputusan verifikasi')
            ->assertSee('Buka bukti pembayaran');

        $this->actingAs($admin)
            ->patch("/admin/payments/{$payment->id}", ['status' => PaymentStatus::PAID->value])
            ->assertRedirect(route('admin.payments.show', $payment))
            ->assertSessionHas('success');

        $this->assertSame(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertSame(PaymentStatus::PAID, $booking->fresh()->payment_status);
        $this->assertSame($admin->id, $payment->fresh()->reviewed_by);
    }

    public function test_rejection_requires_reason_and_non_admin_is_forbidden(): void
    {
        Notification::fake();
        [, , $payment] = $this->createPendingPayment();
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($admin)
            ->patch("/admin/payments/{$payment->id}", ['status' => PaymentStatus::FAILED->value])
            ->assertSessionHasErrors('rejection_reason');

        $this->actingAs($customer)
            ->get('/admin/payments')
            ->assertForbidden();
    }

    private function createPendingPayment(): array
    {
        $customer = User::factory()->customer()->create();
        $package = TourPackage::query()->create([
            'name' => 'Paket Web Payment',
            'slug' => 'paket-web-payment',
            'description' => 'Paket test web payment.',
            'meeting_point' => 'Basecamp',
            'duration_minutes' => 120,
            'minimum_participants' => 1,
            'maximum_participants' => 10,
            'price_per_person' => 125000,
            'status' => TourPackageStatus::ACTIVE,
        ]);
        $booking = Booking::query()->create([
            'booking_code' => 'WEB-PAY-001',
            'customer_id' => $customer->id,
            'tour_package_id' => $package->id,
            'tour_date' => now()->addMonth()->toDateString(),
            'participant_count' => 2,
            'total_amount' => 250000,
            'status' => BookingStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
        ]);
        $payment = Payment::query()->create([
            'booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'amount' => 250000,
            'method' => 'bank_transfer',
            'proof_path' => 'payment-proofs/proof.jpg',
            'status' => PaymentStatus::PENDING,
            'submitted_at' => now(),
        ]);

        return [$customer, $booking, $payment];
    }
}
