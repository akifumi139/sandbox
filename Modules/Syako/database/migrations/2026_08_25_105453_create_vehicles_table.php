<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('syako__vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('vehicle_number')->unique()->comment('車両番号・ナンバープレート');
            $table->string('manufacturer')->nullable()->comment('メーカー');
            $table->string('model')->nullable()->comment('車種');
            $table->string('model_code')->nullable()->comment('型式');

            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syako__vehicles');
    }
};
