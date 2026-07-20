<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booking_participant_vehicle_allocations')) {
            return;
        }

        Schema::create('booking_participant_vehicle_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id');
            $table->foreignId('booking_participant_id');
            $table->foreignId('driver_assignment_id');
            $table->timestamps();

            $table->foreign('booking_id', 'bpva_booking_fk')
                ->references('id')
                ->on('bookings')
                ->cascadeOnDelete();
            $table->foreign('booking_participant_id', 'bpva_participant_fk')
                ->references('id')
                ->on('booking_participants')
                ->cascadeOnDelete();
            $table->foreign('driver_assignment_id', 'bpva_assignment_fk')
                ->references('id')
                ->on('driver_assignments')
                ->cascadeOnDelete();

            $table->unique('booking_participant_id', 'bpva_participant_unique');
            $table->index(['booking_id', 'driver_assignment_id'], 'bpva_booking_assignment_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_participant_vehicle_allocations');
    }
};
