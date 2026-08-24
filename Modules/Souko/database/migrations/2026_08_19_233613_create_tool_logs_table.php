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
        Schema::create('souko__tool_logs', function (Blueprint $table) {
            $table->id();
            // 工具への外部キー参照
            $table->foreignId('tool_id')->constrained('souko__tools')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('user_name')->nullable()->comment('実際に借りた人の名前');
            $table->timestamp('borrow_at')->comment('借用日時');
            $table->timestamp('return_at')->nullable()->comment('返却日時');
            $table->text('note')->nullable()->comment('任意のメモや詳細情報');

            $table->timestamps();

            $table->index(['tool_id', 'borrow_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('souko__tool_logs');
    }
};
