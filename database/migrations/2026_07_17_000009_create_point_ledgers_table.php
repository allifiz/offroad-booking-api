<?php

use App\Enums\PointLedgerType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_profile_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->index();
            $table->unsignedBigInteger('points');
            $table->unsignedBigInteger('available_balance_after');
            $table->unsignedBigInteger('held_balance_after');
            $table->nullableMorphs('reference');
            $table->string('description')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_ledgers');
    }
};
