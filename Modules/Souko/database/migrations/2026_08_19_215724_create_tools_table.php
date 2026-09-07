<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('souko__tools', function (Blueprint $table) {
            $table->id();
            $table->string('management_number')->unique()->comment('社内管理番号');
            $table->string('name')->comment('工具名');
            $table->string('type')->nullable()->index()->comment('工具の種類');
            $table->string('model')->nullable()->comment('型番,種類');
            $table->string('manufacturer')->nullable()->comment('メーカー');
            $table->string('status')->default('available')->comment('工具の状態: available (利用可能), rented (貸出中), maintenance (点検中), lost (紛失) など');
            $table->text('note')->nullable()->comment('備考');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('souko__tools');
    }
};
