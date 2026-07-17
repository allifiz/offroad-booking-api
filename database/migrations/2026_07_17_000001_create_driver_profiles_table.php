<?php

use App\Enums\DriverStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('status', 20)->default(DriverStatus::UNAVAILABLE->value)->index();
            $table->string('license_number', 100)->nullable()->unique();
            $table->string('identity_number', 30)->nullable()->unique();
            $table->text('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('joined_at')->nullable();
            $table->unsignedBigInteger('available_points')->default(0);
            $table->unsignedBigInteger('held_points')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_profiles');
    }
};
