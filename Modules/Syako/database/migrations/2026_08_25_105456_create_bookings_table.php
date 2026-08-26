<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syako__bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('syako__vehicles')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->text('notes')->nullable();
            $table->boolean('has_fuel_card')->default(false);
            $table->boolean('has_etc_card')->default(false);
            $table->timestamps();

            $table->index(['vehicle_id', 'starts_at', 'ends_at']);
            $table->index(['user_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syako__bookings');
    }
};
