<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_leader')->default(false);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['travel_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_group_members');
    }
};
