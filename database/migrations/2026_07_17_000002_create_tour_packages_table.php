<?php

use App\Enums\TourPackageStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 180)->unique();
            $table->text('description')->nullable();
            $table->string('meeting_point')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('minimum_participants')->default(1);
            $table->unsignedInteger('maximum_participants')->nullable();
            $table->decimal('price_per_person', 12, 2);
            $table->string('status', 20)->default(TourPackageStatus::DRAFT->value)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_packages');
    }
};
