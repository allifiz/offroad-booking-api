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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_customer_can_submit_payment_proof_and_booking_becomes_pending(): void
    {
        [$customer, $booking] = $this->createCustomerAndBooking();

        Sanctum::actingAs($customer);

        $response = $this->postJson("/api/v1/customer/bookings/{$booking->id}/payments", [
            'method' => 'bank_transfer',
            'proof' => UploadedFile::fake()->image('proof.jpg'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', PaymentStatus::PENDING->value)
            ->assertJsonPath('data.amount', '250000.00');

        $payment = Payment::query()->firstOrFail();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'status' => PaymentStatus::PENDING->value,
            'method' => 'bank_transfer',
        ]);
        $this->assertSame(PaymentStatus::PENDING, $booking->fresh()->payment_status);
        Storage::disk('public')->assertExists($payment->proof_path);
    }

    public function test_duplicate_pending_payment_is_rejected_without_creating_another_record(): void
    {
        [$customer, $booking] = $this->createCustomerAndBooking();
        $this->createPayment($booking, $customer, PaymentStatus::PENDING);

        Sanctum::actingAs($customer);

        $response = $this->postJson("/api/v1/customer/bookings/{$booking->id}/payments", [
            'method' => 'bank_transfer',
            'proof' => UploadedFile::fake()->image('second-proof.jpg'),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('booking');

        $this->assertSame(1, Payment::query()->count());
        $this->assertSame(PaymentStatus::PENDING, $booking->fresh()->payment_status);
    }

    public function test_admin_approval_marks_payment_and_booking_as_paid(): void
    {
        [$customer, $booking] = $this->createCustomerAndBooking(PaymentStatus::PENDING);
        $payment = $this->createPayment($booking, $customer, PaymentStatus::PENDING);
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/v1/admin/payments/{$payment->id}/verification", [
            'status' => PaymentStatus::PAID->value,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', PaymentStatus::PAID->value);

        $payment->refresh();
        $this->assertSame(PaymentStatus::PAID, $payment->status);
        $this->assertSame($admin->id, $payment->reviewed_by);
        $this->assertNotNull($payment->reviewed_at);
        $this->assertSame(PaymentStatus::PAID, $booking->fresh()->payment_status);
    }

    public function test_rejected_payment_sets_failed_and_customer_can_resubmit(): void
    {
        [$customer, $booking] = $this->createCustomerAndBooking(PaymentStatus::PENDING);
        $payment = $this->createPayment($booking, $customer, PaymentStatus::PENDING);
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/admin/payments/{$payment->id}/verification", [
            'status' => PaymentStatus::FAILED->value,
            'rejection_reason' => 'Bukti transfer tidak terbaca.',
        ])->assertOk()
            ->assertJsonPath('data.status', PaymentStatus::FAILED->value)
            ->assertJsonPath('data.rejection_reason', 'Bukti transfer tidak terbaca.');

        $this->assertSame(PaymentStatus::FAILED, $booking->fresh()->payment_status);

        Sanctum::actingAs($customer);

        $resubmission = $this->postJson("/api/v1/customer/bookings/{$booking->id}/payments", [
            'method' => 'bank_transfer',
            'proof' => UploadedFile::fake()->image('replacement-proof.jpg'),
        ]);

        $resubmission->assertCreated()
            ->assertJsonPath('data.status', PaymentStatus::PENDING->value);

        $this->assertSame(2, Payment::query()->count());
        $this->assertSame(PaymentStatus::PENDING, $booking->fresh()->payment_status);
    }

    public function test_only_pending_payment_can_be_verified_and_rejection_requires_reason(): void
    {
        [$customer, $booking] = $this->createCustomerAndBooking(PaymentStatus::PENDING);
        $payment = $this->createPayment($booking, $customer, PaymentStatus::PENDING);
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/admin/payments/{$payment->id}/verification", [
            'status' => PaymentStatus::FAILED->value,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('rejection_reason');

        $this->patchJson("/api/v1/admin/payments/{$payment->id}/verification", [
            'status' => PaymentStatus::PAID->value,
        ])->assertOk();

        $this->patchJson("/api/v1/admin/payments/{$payment->id}/verification", [
            'status' => PaymentStatus::FAILED->value,
            'rejection_reason' => 'Tidak boleh diproses ulang.',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertSame(PaymentStatus::PAID, $payment->fresh()->status);
        $this->assertSame(PaymentStatus::PAID, $booking->fresh()->payment_status);
    }

    private function createCustomerAndBooking(PaymentStatus $paymentStatus = PaymentStatus::UNPAID): array
    {
        $customer = User::factory()->customer()->create();
        $package = TourPackage::query()->create([
            'name' => 'Paket Payment Test',
            'slug' => 'paket-payment-test-'.fake()->unique()->numberBetween(1, 999999),
            'description' => 'Paket untuk feature test pembayaran.',
            'meeting_point' => 'Basecamp',
            'duration_minutes' => 120,
            'minimum_participants' => 1,
            'maximum_participants' => 10,
            'price_per_person' => 125000,
            'status' => TourPackageStatus::ACTIVE,
        ]);
        $booking = Booking::query()->create([
            'booking_code' => 'PAY-'.fake()->unique()->numerify('######'),
            'customer_id' => $customer->id,
            'tour_package_id' => $package->id,
            'tour_date' => now()->addMonth()->toDateString(),
            'participant_count' => 2,
            'total_amount' => 250000,
            'status' => BookingStatus::PENDING,
            'payment_status' => $paymentStatus,
            'notes' => 'Payment flow test.',
        ]);

        return [$customer, $booking];
    }

    private function createPayment(Booking $booking, User $customer, PaymentStatus $status): Payment
    {
        return Payment::query()->create([
            'booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'amount' => $booking->total_amount,
            'method' => 'bank_transfer',
            'proof_path' => 'payment-proofs/existing-proof.jpg',
            'status' => $status,
            'submitted_at' => now(),
        ]);
    }
}
