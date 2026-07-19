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
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_assignment_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique('booking_participant_id');
            $table->index(['booking_id', 'driver_assignment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_participant_vehicle_allocations');
    }
};