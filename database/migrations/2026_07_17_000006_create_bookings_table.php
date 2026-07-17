<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 30)->unique();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('tour_package_id')->constrained()->restrictOnDelete();
            $table->foreignId('travel_group_id')->nullable()->constrained()->nullOnDelete();
            $table->date('tour_date')->index();
            $table->unsignedInteger('participant_count');
            $table->decimal('total_amount', 14, 2);
            $table->string('status', 20)->default(BookingStatus::PENDING->value)->index();
            $table->string('payment_status', 20)->default(PaymentStatus::UNPAID->value)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
