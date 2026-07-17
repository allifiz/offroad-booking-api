<?php

use App\Enums\VehicleOwnershipType;
use App\Enums\VehicleStatus;
use App\Enums\VerificationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ownership_type', 20)->default(VehicleOwnershipType::COMPANY->value)->index();
            $table->string('name', 100);
            $table->string('plate_number', 20)->unique();
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedInteger('capacity')->default(1);
            $table->string('status', 20)->default(VehicleStatus::AVAILABLE->value)->index();
            $table->string('verification_status', 20)->default(VerificationStatus::APPROVED->value)->index();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
