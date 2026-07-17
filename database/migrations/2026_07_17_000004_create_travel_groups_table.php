<?php

use App\Enums\TravelGroupSource;
use App\Enums\TravelGroupStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('source', 20)->default(TravelGroupSource::WEBSITE->value)->index();
            $table->foreignId('leader_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default(TravelGroupStatus::DRAFT->value)->index();
            $table->unsignedInteger('member_limit')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_groups');
    }
};
